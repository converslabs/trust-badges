const { exec } = require('child_process');
const path = require('path');

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

const main = async () => {
    try {
        console.log('Installing dependencies...');
        await installDeps();
        console.log('Building project...');
        await build();
        console.log('Build completed successfully!');
    } catch (error) {
        console.error('Build failed:', error);
        process.exit(1);
    }
};

main();
