// Memeriksa sintaks semua file PHP dan JavaScript proyek tanpa menjalankan aplikasinya.
import { readdirSync } from 'node:fs';
import { join, extname } from 'node:path';
import { spawnSync } from 'node:child_process';

const php = process.env.PHP_BINARY || 'php';
const files = [];
// Mengumpulkan file PHP dan JavaScript dari folder serta subfoldernya.
function collect(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);
        if (entry.isDirectory()) collect(path);
        else if (['.php', '.js'].includes(extname(path))) files.push(path);
    }
}
collect('app');
collect('views');
collect('assets/js');
files.push('index.php', 'router.php', 'config.php');
let failures = 0;
for (const file of files) {
    const extension = extname(file);
    const command = extension === '.php' ? php : process.execPath;
    const args = extension === '.php' ? ['-l', file] : ['--check', file];
    const result = spawnSync(command, args, { encoding: 'utf8' });
    if (result.status !== 0) {
        failures++;
        console.error(file, result.error?.message || result.stderr || result.stdout);
    }
}
console.log(`${files.length} files checked; ${failures} failures.`);
process.exitCode = failures ? 1 : 0;
