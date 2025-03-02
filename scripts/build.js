const { exec } = require('child_process');
const path = require('path');
const fs = require('fs');
const archiver = require('archiver');

// Clean the release folder
const cleanReleaseFolder = () => {
    return new Promise((resolve, reject) => {
        const releaseDir = path.resolve(__dirname, '../release');
        if (fs.existsSync(releaseDir)) {
            fs.readdir(releaseDir, (err, files) => {
                if (err) reject(err);
                for (const file of files) {
                    fs.unlink(path.join(releaseDir, file), err => {
                        if (err) reject(err);
                    });
                }
                resolve();
            });
        } else {
            resolve();
        }
    });
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
const build = () => {
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
const zipPlugin = (version) => {
    return new Promise((resolve, reject) => {
        const output = fs.createWriteStream(path.resolve(__dirname, `../release/trust-badges-v${version}.zip`));
        const archive = archiver('zip', { zlib: { level: 9 } });

        output.on('close', resolve);
        archive.on('error', reject);
        archive.pipe(output);

        // Add specific directories and files
        archive.directory(path.resolve(__dirname, '../assets'), 'assets');
        archive.directory(path.resolve(__dirname, '../includes'), 'includes');
        archive.directory(path.resolve(__dirname, '../vendor'), 'vendor');
        archive.file(path.resolve(__dirname, '../README.md'), { name: 'readme.md' });
        archive.file(path.resolve(__dirname, '../trust-badges.php'), { name: 'trust-badges.php' });
        archive.file(path.resolve(__dirname, '../composer.json'), { name: 'composer.json' });

        archive.finalize();
    });
};

const main = async () => {
    try {
        console.log('Cleaning release folder...');
        await cleanReleaseFolder();
        console.log('Installing dependencies...');
        await installDeps();
        console.log('Building project...');
        await build();

        // wait 2 seconds
        await new Promise((resolve) => setTimeout(resolve, 2000));

        // Read version from package.json
        const packageJson = require(path.resolve(__dirname, '../package.json'));
        const version = packageJson.version;

        // Ensure release directory exists
        const releaseDir = path.resolve(__dirname, '../release');
        if (!fs.existsSync(releaseDir)) {
            fs.mkdirSync(releaseDir);
        }

        console.log('Creating zip package...');
        await zipPlugin(version);
        console.log('Build and packaging completed successfully!');
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
};

main();