import fs from 'fs';
import os from 'os';
import path from 'path';
import { afterEach, describe, expect, it } from 'vitest';
import {
  checkDomainTrio,
  checkRegistrySync,
  listDomainNames,
  parseRegistryDomainPaths,
} from './check-docs.js';

describe('listDomainNames', () => {
  let tmpDir;

  afterEach(() => {
    if (tmpDir) {
      fs.rmSync(tmpDir, { recursive: true, force: true });
      tmpDir = undefined;
    }
  });

  it('returns only immediate subdirectories', () => {
    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'check-docs-'));
    const domainsDir = path.join(tmpDir, 'app', 'Domains');
    fs.mkdirSync(path.join(domainsDir, 'Alpha'), { recursive: true });
    fs.mkdirSync(path.join(domainsDir, 'Beta'), { recursive: true });

    expect(listDomainNames(tmpDir)).toEqual(['Alpha', 'Beta']);
  });

  it('ignores files at app/Domains root', () => {
    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'check-docs-'));
    const domainsDir = path.join(tmpDir, 'app', 'Domains');
    fs.mkdirSync(domainsDir, { recursive: true });
    fs.writeFileSync(path.join(domainsDir, 'NOT_A_DOMAIN.md'), '# stray file');
    fs.mkdirSync(path.join(domainsDir, 'Real'), { recursive: true });

    expect(listDomainNames(tmpDir)).toEqual(['Real']);
  });
});

describe('checkDomainTrio', () => {
  let tmpDir;

  afterEach(() => {
    if (tmpDir) {
      fs.rmSync(tmpDir, { recursive: true, force: true });
      tmpDir = undefined;
    }
  });

  function writeTrio(domainName, claudeContent = '@AGENTS.md\n') {
    const domainDir = path.join(tmpDir, 'app', 'Domains', domainName);
    fs.mkdirSync(domainDir, { recursive: true });
    fs.writeFileSync(path.join(domainDir, 'README.md'), '# README');
    fs.writeFileSync(path.join(domainDir, 'AGENTS.md'), '# AGENTS');
    fs.writeFileSync(path.join(domainDir, 'CLAUDE.md'), claudeContent);
  }

  it('passes when trio present and CLAUDE shim exact', () => {
    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'check-docs-'));
    writeTrio('Follow');

    expect(checkDomainTrio(tmpDir, 'Follow')).toEqual([]);
  });

  it('fails on missing README, AGENTS, or CLAUDE', () => {
    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'check-docs-'));
    const domainDir = path.join(tmpDir, 'app', 'Domains', 'Follow');
    fs.mkdirSync(domainDir, { recursive: true });
    fs.writeFileSync(path.join(domainDir, 'AGENTS.md'), '# AGENTS');

    expect(checkDomainTrio(tmpDir, 'Follow')).toEqual([
      'app/Domains/Follow: missing README.md',
      'app/Domains/Follow: missing CLAUDE.md',
    ]);
  });

  it('fails when CLAUDE content is not exactly @AGENTS.md newline', () => {
    tmpDir = fs.mkdtempSync(path.join(os.tmpdir(), 'check-docs-'));
    writeTrio('Follow', '@AGENTS.md');

    expect(checkDomainTrio(tmpDir, 'Follow')).toEqual([
      'app/Domains/Follow: CLAUDE.md is not the @AGENTS.md shim',
    ]);
  });
});

describe('parseRegistryDomainPaths', () => {
  it('extracts app/Domains paths from registry table', () => {
    const content = `# Project

## Domain Registry

| Domain | Path | Responsibilities | Tables |
|--------|------|-----------------|--------|
| **Follow** | \`app/Domains/Follow\` | Follow users | \`follow_follows\` |
| **Home** | \`app/Domains/Home\` | Home page | *(none)* |

## Laravel Coding Standards
`;

    expect(parseRegistryDomainPaths(content)).toEqual([
      'app/Domains/Follow',
      'app/Domains/Home',
    ]);
  });

  it('ignores registry paths outside Domain Registry section', () => {
    const content = `# Project

Some other table with \`app/Domains/Orphan\` outside the registry.

## Domain Registry

| Domain | Path | Responsibilities | Tables |
|--------|------|-----------------|--------|
| **Follow** | \`app/Domains/Follow\` | Follow users | \`follow_follows\` |

## Laravel Coding Standards

Another \`app/Domains/AfterSection\` mention.
`;

    expect(parseRegistryDomainPaths(content)).toEqual(['app/Domains/Follow']);
  });
});

describe('checkRegistrySync', () => {
  it('fails when disk domain absent from registry', () => {
    expect(checkRegistrySync(['Follow', 'Home'], ['app/Domains/Follow'])).toEqual([
      'app/Domains/Home: not listed in Domain Registry',
    ]);
  });

  it('fails when registry row points at missing directory', () => {
    expect(checkRegistrySync(['Follow'], ['app/Domains/Follow', 'app/Domains/Ghost'])).toEqual([
      'app/Domains/Ghost: listed in Domain Registry but no directory on disk',
    ]);
  });
});
