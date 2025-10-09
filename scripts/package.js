#!/usr/bin/env node
/*
 Node.js Deployment Script equivalent to deploy-full.sh
 - Supports LOCAL_RUNNER=php to use local PHP/Composer instead of Sail
 - Otherwise uses ./vendor/bin/sail for composer and artisan commands
*/

import fs, { copyFile } from 'fs';
import fsp from 'fs/promises';
import path from 'path';
import readline from 'readline';
import { fileURLToPath } from 'url';
import archiver from 'archiver';
import { makeLog, run, determineRunner, runCapture } from './utils.js';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const projectRoot = path.resolve(__dirname, '..');

const DIST_DIR = 'dist';
const TARGETS = [
  { label: 'test', envFile: '.env.test', robotsFile: 'robots.test.txt' },
  { label: 'prod', envFile: '.env.production', robotsFile: 'robots.production.txt' },
];
const VERSION_FILE = 'version.json';

const log = makeLog('deploy');

// Wrapper to ensure cwd defaults to projectRoot for our run calls
function runHere(cmd, args, options = {}) { return run(cmd, args, { cwd: projectRoot, ...options }); }

function ask(question) {
  const rl = readline.createInterface({ input: process.stdin, output: process.stdout });
  return new Promise((resolve) => rl.question(question, (answer) => {
    rl.close();
    resolve(answer.trim());
  }));
}

async function autoVersion() {
  const attempts = [
    ['git', ['describe', '--tags', '--always', '--dirty']],
    ['git', ['rev-parse', '--short', 'HEAD']],
  ];
  for (const [cmd, args] of attempts) {
    try {
      const out = runCapture(cmd, args, { cwd: projectRoot });
      if (out) return out;
    } catch {}
  }
  return `build-${new Date().toISOString().replace(/[:.]/g, '-')}`;
}

function getCommitSha(ref = 'HEAD') {
  try {
    const out = runCapture('git', ['rev-parse', ref], { cwd: projectRoot });
    if (out) return out;
  } catch {}
  return null;
}

function sanitizeForFilename(value) {
  return value.replace(/[^a-zA-Z0-9._-]+/g, '-');
}

function shortSha(sha) {
  return sha ? sha.slice(0, 7) : 'unknown';
}

function showRecentCommits(limit = 10) {
  try {
    const history = runCapture('git', ['log', `-n${limit}`, '--oneline'], { cwd: projectRoot });
    if (history) {
      log(null, 'Recent commits:');
      log.raw(history.split('\n').map((line) => `  ${line}`).join('\n'));
    }
  } catch {
    log.warn('Unable to read git history (is this a git repository?).');
  }
}

function getLatestTag() {
  try {
    const tag = runCapture('git', ['describe', '--tags', '--abbrev=0'], { cwd: projectRoot });
    if (tag) {
      return tag;
    }
  } catch {}
  return null;
}

function runner() {
  const chosen = determineRunner();
  if (chosen === 'php') {
    return {
      composer: (args) => runHere('composer', args, { shell: process.platform === 'win32' }),
      artisan: (args) => runHere('php', ['artisan', ...args], { shell: process.platform === 'win32' }),
      composerIn: (relativeDir, args) => runHere('composer', [...args, `--working-dir=${path.join(projectRoot, relativeDir)}`], { shell: process.platform === 'win32' }),
      npm: (args) => runHere('npm', args, { shell: process.platform === 'win32' }),
    };
  }
  const sailPath = path.join(projectRoot, 'vendor', 'bin', 'sail');
  const toContainerPath = (relativeDir) => `/var/www/html/${relativeDir.split(path.sep).join('/')}`;
  // On Windows, sail is a bash script; execute via bash if available
  const sailRunner = (subArgs) => {
    if (process.platform === 'win32') {
      // Prefer bash if present in PATH (e.g., Git Bash); fallback to wsl if available
      return runHere('bash', [sailPath, ...subArgs]);
    }
    return runHere(sailPath, subArgs);
  };
  return {
    composer: (args) => sailRunner(['composer', ...args]),
    artisan: (args) => sailRunner(['artisan', ...args]),
    composerIn: (relativeDir, args) => sailRunner(['composer', ...args, `--working-dir=${toContainerPath(relativeDir)}`]),
    npm: (args) => runHere(['npm', ...args]),
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

async function withEnvFile(envFile, callback) {
  const envPath = path.join(projectRoot, '.env');
  const backupPath = `${envPath}.deploy-backup`;
  const sourcePath = path.join(projectRoot, envFile);

  if (!(await exists(sourcePath))) {
    throw new Error(`${envFile} not found. Please create it before deployment.`);
  }

  const hadEnv = await exists(envPath);
  if (hadEnv) {
    await fsp.copyFile(envPath, backupPath);
  }

  await fsp.copyFile(sourcePath, envPath);

  try {
    await callback();
  } finally {
    if (hadEnv) {
      await fsp.copyFile(backupPath, envPath);
      await fsp.rm(backupPath, { force: true });
    } else {
      await fsp.rm(envPath, { force: true });
    }
  }
}

async function copyRecursive(src, dest) {
  // Use lstat to detect symlinks without following them first
  const lst = await fsp.lstat(src);
  if (lst.isSymbolicLink()) {
    // Resolve the symlink target; if it can't be resolved, skip with a warning
    let target;
    try {
      target = await fsp.realpath(src);
    } catch (e) {
      console.warn(`⚠️  Skipping broken symlink: ${src}`);
      return;
    }
    // Recursively copy the resolved target into destination
    await copyRecursive(target, dest);
    return;
  }
  if (lst.isDirectory()) {
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
  } else if (lst.isFile()) {
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

async function zipDist(sourceDir, zipName) {
  const outputDir = path.join(projectRoot, DIST_DIR);
  await ensureDir(outputDir);
  const zipPath = path.join(outputDir, zipName);
  try {
    await fsp.rm(zipPath, { force: true });
  } catch {}
  await new Promise((resolve, reject) => {
    const output = fs.createWriteStream(zipPath);
    const archive = archiver('zip', { zlib: { level: 9 } });
    output.on('close', resolve);
    archive.on('error', reject);
    archive.pipe(output);
    const ensureNames = ['.env', VERSION_FILE];
    archive.glob('**/*', { cwd: sourceDir, dot: true, ignore: [zipName, ...ensureNames] });
    for (const name of ensureNames) {
      const abs = path.join(sourceDir, name);
      if (fs.existsSync(abs)) {
        archive.file(abs, { name });
      }
    }
    archive.finalize();
  });
  const { size } = await fsp.stat(zipPath);
  return { zipPath, size };
}

async function checkAtRootDirectory() {
  // Preconditions
  if (!(await exists(path.join(projectRoot, 'artisan')))) {
    throw new Error('Not in a Laravel project directory');
  }
}

async function determineVersionNumber(){
  showRecentCommits();
  const latestTag = getLatestTag();
  if (latestTag) {
    log(null, `Latest tag: ${latestTag}`);
  } else {
    log(null, 'Latest tag: (none found)');
  }
  const manualTag = await ask('Enter a deployment version/tag (leave blank to auto-generate): ');
  const version = manualTag || await autoVersion();
  const sanitizedVersion = sanitizeForFilename(version);
  const headShaRaw = getCommitSha();
  const commitSha = headShaRaw || 'unknown';
  let tagExistsOnHead = false;

  if (manualTag) {
    const existingTagSha = getCommitSha(manualTag);
    if (existingTagSha) {
      if (!headShaRaw) {
        log.warn(`Tag '${manualTag}' already exists but current commit SHA could not be determined.`);
        tagExistsOnHead = true;
      } else if (existingTagSha !== headShaRaw) {
        throw new Error(`Tag '${manualTag}' already exists on ${shortSha(existingTagSha)}, but current commit is ${shortSha(headShaRaw)}. Aborting.`);
      } else {
        log(null, `Tag '${manualTag}' already exists on the current commit (${shortSha(headShaRaw)}).`);
        tagExistsOnHead = true;
      }
    }
  }
  log(null, `Selected version: ${sanitizedVersion}`);

  return {commitSha, sanitizedVersion, version, manualTag, tagExistsOnHead};
}

async function cleanDist() {
  const distRootPath = path.join(projectRoot, DIST_DIR);
  if (await exists(distRootPath)) {
    await rimraf(distRootPath);
    log('ok', 'Removed existing dist directory');
  }
  await ensureDir(distRootPath);
}

async function copyToDist() {
  const toCopyDirs = ['app', 'bootstrap', 'config', 'public', 'resources', 'routes', 'storage'];

  // Copy all above folders to dist/base
  const baseRelative = path.join(DIST_DIR, 'base');
  const basePath = path.join(projectRoot, baseRelative);
  await ensureDir(basePath);

  for (const d of toCopyDirs) {
    await copyRecursive(path.join(projectRoot, d), path.join(basePath, d));
  }

  // In storage, remove app/public entries, 
  // because these are local images of files that we do not want to send
  const storagePublicBase = path.join(basePath, 'storage', 'app', 'public');
  if (await exists(storagePublicBase)) {
    const entries = await fsp.readdir(storagePublicBase);
    for (const name of entries) {
      await rimraf(path.join(storagePublicBase, name));
    }
  }

  // Copy a few remaining files
  await copyRecursive(path.join(projectRoot, 'artisan'), path.join(basePath, 'artisan'));
  await copyRecursive(path.join(projectRoot, 'composer.json'), path.join(basePath, 'composer.json'));
  await copyRecursive(path.join(projectRoot, '.env'), path.join(basePath, '.env'));
  if (await exists(path.join(projectRoot, 'composer.lock'))) {
    await copyRecursive(path.join(projectRoot, 'composer.lock'), path.join(basePath, 'composer.lock'));
  }

  return baseRelative;
}

async function setupPermissions(distSourcePath) {
  await chmodRecursive(path.join(distSourcePath, 'storage'), 0o644, 0o755);
  await chmodRecursive(path.join(distSourcePath, 'bootstrap', 'cache'), 0o644, 0o755);
  try { await fsp.chmod(path.join(distSourcePath, 'storage'), 0o755); } catch {}
  try { await fsp.chmod(path.join(distSourcePath, 'bootstrap', 'cache'), 0o755); } catch {}

}

async function setupPublicHtmlDir(basePath) {
  const publicHtmlBasePath = path.join(basePath, 'public_html');
  await ensureDir(publicHtmlBasePath);
  const publicPath = path.join(projectRoot, 'public');
  if (await exists(publicPath)) {
    const resolvedPublicPath = await fsp.realpath(publicPath).catch(() => publicPath);
    const entries = await fsp.readdir(resolvedPublicPath, { withFileTypes: true });
    for (const ent of entries) {
      if (ent.name === '.htaccess') continue;
      await copyRecursive(path.join(resolvedPublicPath, ent.name), path.join(publicHtmlBasePath, ent.name));
    }
    if (await exists(path.join(resolvedPublicPath, '.htaccess'))) {
      await copyRecursive(path.join(resolvedPublicPath, '.htaccess'), path.join(publicHtmlBasePath, '.htaccess'));
      log(null, '✅ .htaccess file copied');
    } else {
      log(null, '⚠️  Warning: .htaccess file not found in public directory');
    }
  }
}

async function packageForEnv(target, distSourcePath, version, sanitizedVersion, commitSha) {
  await fsp.copyFile(path.join(projectRoot, target.envFile), path.join(distSourcePath, '.env'));
  await fsp.copyFile(path.join(projectRoot, target.robotsFile), path.join(distSourcePath, 'robots.txt'));

    const metadata = {
      version,
      sanitizedVersion,
      commitSha,
      builtAt: new Date().toISOString(),
      environment: target.label,
      envFile: target.envFile,
    };
    await fsp.writeFile(path.join(distSourcePath, VERSION_FILE), `${JSON.stringify(metadata, null, 2)}\n`, 'utf8');

    const zipName = `esperluettes-${target.label}-${sanitizedVersion}.zip`;
    const { zipPath, size } = await zipDist(distSourcePath, zipName);
    const sizeMB = (size / (1024 * 1024)).toFixed(2) + ' MB';

    log(null, `Generated ${zipPath} (${sizeMB})`);
}

async function tagGit(manualTag, tagExistsOnHead) {
  if (manualTag) {
    if (tagExistsOnHead) {
      log(null, `Tag '${manualTag}' already exists on this commit; skipping creation.`);
    } else {
      log(null, `Tagging current commit with '${manualTag}'...`);
      try {
        runHere('git', ['tag', manualTag]);
        log('ok', `Created tag '${manualTag}'.`);
        try {
          runHere('git', ['push', 'origin', manualTag]);
          log('ok', `Pushed tag '${manualTag}' to origin.`);
        } catch (pushErr) {
          log.warn(`Failed to push tag '${manualTag}' to origin: ${pushErr.message}`);
        }
      } catch (tagErr) {
        log.warn(`Failed to create git tag '${manualTag}': ${tagErr.message}`);
      }
    }
    log(null, '');
  }
}

async function main() {
  log('header', 'Starting Laravel Deployment Build Process');
  log(null, '==============================================');
  const r = runner();

  await checkAtRootDirectory();
  const {commitSha, sanitizedVersion, version, manualTag, tagExistsOnHead} = await determineVersionNumber();


  log(null, '📦 Step 1: Cleaning previous build artifacts');
  await cleanDist();

  log(null, '🧹 Step 2: Clearing Laravel caches to avoid copying them to other envs');
  r.artisan(['optimize:clear']);

  log(null, '📦 Step 3: Copying base files into dist');
  const distSourcePath = await copyToDist();

  log(null, '🏗️ Step 4: Rebuilding front and sending it to dist as well');
  r.npm(['run', 'build']);

  log(null, '📦 Step 5: installing vendor folder and optimizing')
  const composerArgs = ['install', '--optimize-autoloader', '--no-dev', '--no-interaction'];
  r.composerIn(distSourcePath, composerArgs);

  log(null, '📦 Step 6: Putting all public files into public_html')
  await setupPublicHtmlDir(distSourcePath);

  log(null, 'Step 7: tuning permissions');
  await setupPermissions(distSourcePath);
  
  for (const target of TARGETS) {
    log(null, `📁 Step 8 (${target.label}): Packaging environment`);

    await packageForEnv(target, distSourcePath, version, sanitizedVersion, commitSha);
  }

  log(null, 'Step 9: Tagging git');
  await tagGit(manualTag, tagExistsOnHead);

  log(null, '\n\u001b[32m🎉 Deployment packages created successfully!\u001b[0m');
  log(null, '==============================================');
 
  log(null, '');
  log(null, `Version: ${version}`);
  log(null, `Commit: ${commitSha}`);
  log(null, `Built at: ${new Date().toISOString()}`);
  log(null, '');
  log(null, '\u001b[33m📖 Next steps:\u001b[0m');
  log(null, '1. Upload the desired zip to the server');
  log(null, "2. Run migrations if needed: ./vendor/bin/sail artisan migrate --env=<environment>");
  log(null, "3. After deploy: php artisan optimize:clear (on the server)");
  log(null, '');
 
  log(null, '\u001b[32mHappy deploying! 🚀\u001b[0m');
}

main().catch((err) => {
  console.error('\u001b[31m❌ Error:\u001b[0m', err.message);
  process.exit(1);
});
