#!/usr/bin/env node
/*
 Node.js Deployment Script equivalent to deploy-full.sh
 - Supports LOCAL_RUNNER=php to use local PHP/Composer instead of Sail
 - Otherwise uses ./vendor/bin/sail for composer and artisan commands
*/

import fs from 'fs';
import fsp from 'fs/promises';
import path from 'path';
import { fileURLToPath } from 'url';
import { spawnSync } from 'child_process';
import archiver from 'archiver';
import { parse as dotenvParse } from 'dotenv';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');

const DIST_DIR = 'dist';
const ENV_FILE = '.env.test';
const LOCAL_RUNNER = process.env.LOCAL_RUNNER || '';

function log(section, msg) {
  const BLUE = '\u001b[34m';
  const GREEN = '\u001b[32m';
  const YELLOW = '\u001b[33m';
  const NC = '\u001b[0m';
  const icon = section === 'header' ? `${BLUE}🚀${NC}` : section === 'ok' ? `${GREEN}✅${NC}` : section === 'warn' ? `${YELLOW}⚠️${NC}` : '';
  console.log(`${icon} ${msg}`);
}

function fileExists(p) { try { return fs.existsSync(p); } catch { return false; } }

function run(cmd, args, options = {}) {
  const isWin = process.platform === 'win32';
  const joined = [cmd, ...args].join(' ');
  log(null, `Running ${joined}`);
  // First try without a shell
  let res = spawnSync(cmd, args, { stdio: 'inherit', cwd: projectRoot, shell: false, ...options });
  if ((res.error || res.status !== 0) && isWin) {
    // Fallback: run through shell so Git Bash/CMD can resolve composer/composer.cmd and shims
    const cmdline = [cmd, ...args.map(a => /\s/.test(a) ? `"${a}"` : a)].join(' ');
    log('warn', `Retrying via shell: ${cmdline}`);
    res = spawnSync(cmdline, { stdio: 'inherit', cwd: projectRoot, shell: true, ...options });
  }
  if (res.error) throw res.error;
  if (res.status !== 0) throw new Error(`Command failed: ${joined}`);
}

function determineRunner() {
  let localRunner = process.env.LOCAL_RUNNER;
  if (!localRunner) {
    const envFile = path.resolve(process.cwd(), '.env');
    if (fileExists(envFile)) {
      try { 
        localRunner = dotenvParse(fs.readFileSync(envFile, 'utf8')).LOCAL_RUNNER; 
      } catch (e) {
      }
    }
  }
  localRunner = (localRunner || '').trim().toLowerCase();
  if (!localRunner || !['php', 'sail'].includes(localRunner)) localRunner = 'sail';
  return localRunner;
}

function runner() {
  const chosen = determineRunner();
  if (chosen === 'php') {
    return {
      composer: (args) => run('composer', args, { shell: process.platform === 'win32' }),
      artisan: (args) => run('php', ['artisan', ...args], { shell: process.platform === 'win32' }),
      composerInDist: (args) => run('composer', [...args, `--working-dir=${path.join(projectRoot, DIST_DIR)}`], { shell: process.platform === 'win32' }),
    };
  }
  const sailPath = path.join(projectRoot, 'vendor', 'bin', 'sail');
  // On Windows, sail is a bash script; execute via bash if available
  const sailRunner = (subArgs) => {
    if (isWin) {
      // Prefer bash if present in PATH (e.g., Git Bash); fallback to wsl if available
      if (process.env.COMSPEC && process.env.COMSPEC.toLowerCase().includes('cmd.exe')) {
        // Try bash first
        return run('bash', [sailPath, ...subArgs]);
      }
      return run('bash', [sailPath, ...subArgs]);
    }
    return run(sailPath, subArgs);
  };
  return {
    composer: (args) => sailRunner(['composer', ...args]),
    artisan: (args) => sailRunner(['artisan', ...args]),
    // Inside Sail, the app path is /var/www/html; dist will be available there
    composerInDist: (args) => sailRunner(['composer', ...args, '--working-dir=/var/www/html/dist']),
  };
}

async function exists(p) {
  try { await fsp.access(p); return true; } catch { return false; }
}

async function rimraf(target) {
  await fsp.rm(target, { recursive: true, force: true });
}

async function ensureDir(d) {
  await fsp.mkdir(d, { recursive: true });
}

async function copyRecursive(src, dest) {
  const stat = await fsp.stat(src);
  if (stat.isDirectory()) {
    await ensureDir(dest);
    const entries = await fsp.readdir(src, { withFileTypes: true });
    for (const ent of entries) {
      const s = path.join(src, ent.name);
      const d = path.join(dest, ent.name);
      if (ent.isDirectory()) {
        await copyRecursive(s, d);
      } else if (ent.isFile()) {
        await fsp.copyFile(s, d);
      }
    }
  } else if (stat.isFile()) {
    await ensureDir(path.dirname(dest));
    await fsp.copyFile(src, dest);
  }
}

async function chmodRecursive(root, modeFile, modeDir) {
  if (!(await exists(root))) return;
  const entries = await fsp.readdir(root, { withFileTypes: true });
  for (const ent of entries) {
    const p = path.join(root, ent.name);
    if (ent.isDirectory()) {
      try { await fsp.chmod(p, modeDir); } catch {}
      await chmodRecursive(p, modeFile, modeDir);
    } else if (ent.isFile()) {
      try { await fsp.chmod(p, modeFile); } catch {}
    }
  }
}

async function zipDist(distDir) {
  const zipPath = path.join(distDir, 'esperluettes.zip');
  await new Promise((resolve, reject) => {
    const output = fs.createWriteStream(zipPath);
    const archive = archiver('zip', { zlib: { level: 9 } });
    output.on('close', resolve);
    archive.on('error', reject);
    archive.pipe(output);
    // Add contents of dist excluding the zip itself
    archive.glob('**/*', { cwd: distDir, dot: true, ignore: ['esperluettes.zip'] });
    archive.finalize();
  });
  const { size } = await fsp.stat(zipPath);
  return { zipPath, size };
}

async function main() {
  log('header', 'Starting Laravel Deployment Build Process');
  console.log('==============================================');

  // Preconditions
  if (!(await exists(path.join(projectRoot, 'artisan')))) {
    throw new Error('Not in a Laravel project directory');
  }
  if (!(await exists(path.join(projectRoot, ENV_FILE)))) {
    throw new Error(`${ENV_FILE} not found. Please create it before deployment.`);
  }

  // Step 1: Clean previous build
  log(null, '📦 Step 1: Cleaning previous build');
  const distPath = path.join(projectRoot, DIST_DIR);
  if (await exists(distPath)) {
    await rimraf(distPath);
    log('ok', 'Removed existing dist directory');
  }

  // Step 2: Dependencies
  log(null, '🔧 Step 2: Installing/updating dependencies');
  const r = runner();
  // PHP deps (production optimize in workspace)
  r.composer(['install', '--optimize-autoloader', '--no-interaction']);
  log('ok', 'Composer dependencies installed');

  // Frontend deps (build is separate)
  run(process.platform === 'win32' ? 'npm.cmd' : 'npm', ['ci']);
  log('ok', 'Frontend dependencies installed');
  console.log("ℹ️  Note: Make sure to run 'npm run build' before running this deployment script");

  // Step 3: Clear caches
  log(null, '🧹 Step 3: Clearing Laravel caches');
  r.artisan(['config:clear']);
  r.artisan(['cache:clear']);
  r.artisan(['route:clear']);
  r.artisan(['view:clear']);
  log('ok', 'Laravel caches cleared');

  // Step 4: Optimize for production
  log(null, '⚡ Step 4: Optimizing Laravel for production');
  // Copy production environment (temp)
  await fsp.copyFile(path.join(projectRoot, ENV_FILE), path.join(projectRoot, '.env.temp'));
  r.artisan(['config:cache']);
  r.artisan(['route:cache']);
  r.artisan(['view:cache']);
  r.artisan(['optimize']);
  // Restore original .env (mirror original script behavior)
  if (await exists(path.join(projectRoot, '.env.backup'))) {
    await fsp.rename(path.join(projectRoot, '.env.backup'), path.join(projectRoot, '.env'));
  } else if (await exists(path.join(projectRoot, '.env.temp'))) {
    await fsp.rm(path.join(projectRoot, '.env.temp'), { force: true });
  }
  log('ok', 'Laravel optimized for production');

  // Step 5: Create distribution package
  log(null, '📁 Step 5: Creating distribution package');
  await ensureDir(distPath);

  // Copy essential Laravel files and directories
  const toCopyDirs = ['app', 'bootstrap', 'config', 'public', 'resources', 'routes', 'storage'];
  for (const d of toCopyDirs) {
    await copyRecursive(path.join(projectRoot, d), path.join(distPath, d));
  }

  // Remove dist/storage/app/public/*
  const storagePublic = path.join(distPath, 'storage', 'app', 'public');
  if (await exists(storagePublic)) {
    const entries = await fsp.readdir(storagePublic);
    for (const name of entries) {
      await rimraf(path.join(storagePublic, name));
    }
  }

  // Copy root files
  await copyRecursive(path.join(projectRoot, 'artisan'), path.join(distPath, 'artisan'));
  await copyRecursive(path.join(projectRoot, 'composer.json'), path.join(distPath, 'composer.json'));
  if (await exists(path.join(projectRoot, 'composer.lock'))) {
    await copyRecursive(path.join(projectRoot, 'composer.lock'), path.join(distPath, 'composer.lock'));
  }
  await copyRecursive(path.join(projectRoot, ENV_FILE), path.join(distPath, '.env'));

  // Install prod deps in dist (no-dev)
  r.composerInDist(['install', '--optimize-autoloader', '--no-dev', '--no-interaction']);
  log('ok', 'Core Laravel files copied');

  // Create public_html and copy public files (including .htaccess)
  console.log('Creating public_html structure...');
  const publicHtmlPath = path.join(distPath, 'public_html');
  await ensureDir(publicHtmlPath);
  const publicPath = path.join(projectRoot, 'public');
  if (await exists(publicPath)) {
    // Copy contents of public (excluding dotfiles by default), then copy .htaccess explicitly if present
    const entries = await fsp.readdir(publicPath, { withFileTypes: true });
    for (const ent of entries) {
      if (ent.name === '.htaccess') continue; // handled below
      await copyRecursive(path.join(publicPath, ent.name), path.join(publicHtmlPath, ent.name));
    }
    if (await exists(path.join(publicPath, '.htaccess'))) {
      await copyRecursive(path.join(publicPath, '.htaccess'), path.join(publicHtmlPath, '.htaccess'));
      console.log('✅ .htaccess file copied');
    } else {
      console.log('⚠️  Warning: .htaccess file not found in public directory');
    }
  }
  log('ok', 'Public files prepared for shared hosting');

  // Step 6: Permissions
  log(null, '🔒 Step 6: Setting proper permissions');
  await chmodRecursive(path.join(distPath, 'storage'), 0o644, 0o755);
  await chmodRecursive(path.join(distPath, 'bootstrap', 'cache'), 0o644, 0o755);
  try { await fsp.chmod(path.join(distPath, 'storage'), 0o755); } catch {}
  try { await fsp.chmod(path.join(distPath, 'bootstrap', 'cache'), 0o755); } catch {}
  log('ok', 'Permissions set');

  // Step 7: Zip
  log(null, '📝 Step 7: Creating zip file');
  const { zipPath, size } = await zipDist(distPath);

  const sizeMB = (size / (1024 * 1024)).toFixed(2) + ' MB';
  console.log('\n\u001b[32m🎉 Deployment package created successfully!\u001b[0m');
  console.log('==============================================');
  console.log(`📦 Package location: ${DIST_DIR}/`);
  console.log(`📏 Package size: ${sizeMB}`);
  console.log('');
  console.log('\u001b[33m📖 Next steps:\u001b[0m');
  console.log("1. Push the zip file to the FTP");
  console.log("2. Launch migrations if needed: ./vendor/bin/sail artisan config:clear && ./vendor/bin/sail artisan migrate --env=<environment> && ./vendor/bin/sail artisan config:clear");
  console.log("3. Run (safely): if [ -f esperluettes.zip ]; then rm -rf app bootstrap config database public resources routes vendor && unzip -o esperluettes.zip; else echo '❌ esperluettes.zip not found, aborting'; fi");
  console.log('');
  console.log('\u001b[32mHappy deploying! 🚀\u001b[0m');
}

main().catch((err) => {
  console.error('\u001b[31m❌ Error:\u001b[0m', err.message);
  process.exit(1);
});
