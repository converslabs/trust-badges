const { exec } = require('child_process');
const path = require('path');
const fs = require('fs');
const archiver = require('archiver');
// const { build } = require('vite');
// const viteConfig = require('../vite.config.ts');

// Clean the release folder
const cleanReleaseFolder = () => {
    // rm -rf: the release folder contains both zips and an extracted
    // trust-badges/ directory, and fs.unlink cannot remove directories.
    const releaseDir = path.resolve(__dirname, '../release');
    fs.rmSync(releaseDir, { recursive: true, force: true });
    fs.mkdirSync(releaseDir, { recursive: true });
    return Promise.resolve();
};

// Run npm install if node_modules doesn't exist
const installDeps = () => {
    return new Promise((resolve, reject) => {
        exec('npm install', {
            cwd: path.resolve(__dirname, '..')
        }, (error) => {
            if (error) {
                reject(error);
                return;
            }
            resolve();
        });
    });
};

// Build the project
const buildProject = () => {
    return new Promise((resolve, reject) => {
        exec('npm run build', {
            cwd: path.resolve(__dirname, '..')
        }, (error) => {
            if (error) {
                reject(error);
                return;
            }
            resolve();
        });
    });
};

// Zip the plugin folder
// The version lives in four places. They drifting apart is how v1.1.0 shipped
// in a file named trust-badges-v1.0.0.zip, so the build refuses to run when
// they disagree.
const assertVersionsMatch = (version) => {
    const root = path.resolve(__dirname, '..');
    const pluginPhp = fs.readFileSync(path.join(root, 'trust-badges.php'), 'utf8');
    const readme = fs.readFileSync(path.join(root, 'readme.txt'), 'utf8');

    const found = {
        'package.json': version,
        'trust-badges.php header': (pluginPhp.match(/^\s*\*\s*Version:\s*(.+)$/m) || [])[1]?.trim(),
        'TRUST_BADGES_VERSION': (pluginPhp.match(/TRUST_BADGES_VERSION',\s*'([^']+)'/) || [])[1],
        'readme.txt stable tag': (readme.match(/^Stable tag:\s*(.+)$/m) || [])[1]?.trim(),
    };

    const mismatched = Object.entries(found).filter(([, v]) => v !== version);
    if (mismatched.length) {
        throw new Error(
            'Version mismatch across release files:\n' +
            Object.entries(found).map(([k, v]) => `  ${k}: ${v ?? '(not found)'}`).join('\n')
        );
    }

    if (!new RegExp(`^=\\s*${version.replace(/\./g, '\\.')}\\s*=$`, 'm').test(readme)) {
        throw new Error(`readme.txt has no changelog entry for version ${version}`);
    }
};

// Files that must never end up in a distributed build. Hidden files, source
// maps and VCS checkouts are all rejected by the WordPress.org plugin review.
const EXCLUDED = [
    '**/.DS_Store',
    '**/Thumbs.db',
    '**/*.map',
    '**/.svn/**',
    '**/.git/**',
];

// Staged inside release/trust-badges/ as well as zipped, because scripts/svn.sh
// copies from that directory when tagging a release.
const stagePlugin = () => {
    const root = path.resolve(__dirname, '..');
    const stageDir = path.resolve(root, 'release/trust-badges');
    fs.mkdirSync(stageDir, { recursive: true });

    const skip = (src) =>
        !/(?:\.DS_Store|Thumbs\.db|\.map)$/.test(src) &&
        !/[/\\]\.(?:svn|git)(?:[/\\]|$)/.test(src);

    // All three are required. vendor/ in particular is produced by
    // `composer install`, not by `npm install`, so it can legitimately be
    // missing - and a zip without vendor/autoload.php fatals on activation
    // with "Class not found". Fail loudly rather than shipping that.
    for (const dir of ['assets', 'includes', 'vendor']) {
        const from = path.resolve(root, dir);
        if (!fs.existsSync(from)) {
            throw new Error(
                `Required release directory is missing: ${dir}/` +
                (dir === 'vendor' ? ' — run `composer install` first.' : '')
            );
        }
        fs.cpSync(from, path.join(stageDir, dir), { recursive: true, filter: skip });
    }

    if (!fs.existsSync(path.join(stageDir, 'vendor/autoload.php'))) {
        throw new Error('vendor/autoload.php is missing from the staged build — run `composer install` first.');
    }

    for (const file of ['trust-badges.php', 'composer.json', 'readme.txt']) {
        const from = path.resolve(root, file);
        if (!fs.existsSync(from)) {
            throw new Error(`Required release file is missing: ${file}`);
        }
        fs.copyFileSync(from, path.join(stageDir, file));
    }

    return stageDir;
};

const zipPlugin = (version, stageDir) => {
    return new Promise((resolve, reject) => {
        const output = fs.createWriteStream(path.resolve(__dirname, `../release/trust-badges-v${version}.zip`));
        const archive = archiver('zip', { zlib: { level: 9 } });

        output.on('close', resolve);
        archive.on('error', reject);
        archive.pipe(output);

        // Zip the staged tree so the zip and the SVN payload are identical.
        archive.glob('**/*', { cwd: stageDir, dot: false, ignore: EXCLUDED }, { prefix: 'trust-badges' });

        archive.finalize();
    });
};

// const buildUncompressed = async () => {
//   const uncompressedConfig = {
//     ...viteConfig.default,
//     build: {
//       minify: false,
//       outDir: 'assets/uncompressed',
//       sourcemap: false,
//       rollupOptions: {
//         ...viteConfig.default.build.rollupOptions
//       }
//     }
//   };
//   await build(uncompressedConfig);
// };

const main = async () => {
    try {
        console.log('Cleaning release folder...');
        await cleanReleaseFolder();
        console.log('Installing dependencies...');
        await installDeps();
        console.log('Building project...');
        await buildProject();
        // await buildUncompressed();

        // wait 2 seconds
        await new Promise((resolve) => setTimeout(resolve, 2000));

        // Read version from package.json
        const packageJson = require(path.resolve(__dirname, '../package.json'));
        const version = packageJson.version;

        assertVersionsMatch(version);

        console.log('Staging plugin files...');
        const stageDir = stagePlugin();

        console.log('Creating zip package...');
        await zipPlugin(version, stageDir);
        console.log(`Build and packaging completed successfully! (v${version})`);
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
};

main();