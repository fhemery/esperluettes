import { readFileSync } from 'node:fs';
import path from 'node:path';
import { type Locator } from '@playwright/test';
import { ROOT } from '../support/sail';

/** What a native `<audio>` element reports once it has actually decoded something. */
export interface AudioProbe {
  /** Seconds, or NaN when the media never loaded. */
  duration: number;
  /** Where playback stands after the seek and the short play. */
  currentTime: number;
  /** `MediaError.code`, or 0. */
  error: number;
}

/**
 * Component object for `<x-media::sound-field>`.
 *
 * Pure Alpine: the drop zone and the player are two `<template x-if>` branches,
 * so exactly one of them exists in the DOM at a time. The file input and the
 * `{name}_remove` flag are always there, hidden.
 */
export class SoundField {
  constructor(readonly root: Locator) {}

  get dropZone(): Locator {
    return this.root.locator('div.border-dashed');
  }

  get player(): Locator {
    return this.root.locator('audio');
  }

  get fileInput(): Locator {
    return this.root.locator('input[type="file"]');
  }

  get removeFlag(): Locator {
    return this.root.locator('input[type="hidden"][name$="_remove"]');
  }

  /** The × shown for a file picked in this page load, not yet saved. */
  get cancelButton(): Locator {
    return this.root.getByTitle('Annuler le téléchargement');
  }

  /** The bin shown for the sound already saved on the gift. */
  get deleteButton(): Locator {
    return this.root.getByTitle('Supprimer le fichier audio');
  }

  /** The `x-text` name / size line under the player. */
  get fileInfo(): Locator {
    return this.root.locator('audio + div');
  }

  get error(): Locator {
    return this.root.locator('ul li');
  }

  async pick(fixture: string): Promise<void> {
    await this.fileInput.setInputFiles(path.join(ROOT, fixture));
  }

  /** Any bytes under any name — for the files the server must refuse. */
  async pickRaw(name: string, mimeType: string, buffer: Buffer): Promise<void> {
    await this.fileInput.setInputFiles({ name, mimeType, buffer });
  }

  /**
   * A real drop: a `DataTransfer` holding the file, dispatched on the drop
   * zone, which is what `handleDrop()` reads.
   */
  async drop(name: string, mimeType: string, buffer: Buffer): Promise<void> {
    const page = this.root.page();
    const dataTransfer = await page.evaluateHandle(
      ({ name, mimeType, b64 }) => {
        const bytes = Uint8Array.from(atob(b64), (c) => c.charCodeAt(0));
        const dt = new DataTransfer();
        dt.items.add(new File([bytes], name, { type: mimeType }));
        return dt;
      },
      { name, mimeType, b64: buffer.toString('base64') },
    );
    await this.dropZone.dispatchEvent('dragover', { dataTransfer });
    await this.dropZone.dispatchEvent('drop', { dataTransfer });
  }

  async dropFixture(fixture: string, mimeType: string): Promise<void> {
    await this.drop(path.basename(fixture), mimeType, readFileSync(path.join(ROOT, fixture)));
  }
}

/**
 * Load a native player, seek to `seekTo` seconds and play (muted, so no
 * autoplay policy gets a say) for a moment.
 *
 * The scrubber of the native controls lives in a closed shadow root no driver
 * can reach; dragging it sets `currentTime`, which is what this does.
 */
export async function probeAudio(audio: Locator, seekTo: number): Promise<AudioProbe> {
  return audio.evaluate(async (el: HTMLAudioElement, seekTo: number) => {
    const once = (event: string, ms: number) =>
      new Promise<void>((resolve) => {
        const timer = setTimeout(resolve, ms);
        el.addEventListener(event, () => { clearTimeout(timer); resolve(); }, { once: true });
      });

    if (el.readyState < 1) {
      const loaded = once('loadedmetadata', 8000);
      el.load();
      await loaded;
    }
    if (el.error || !Number.isFinite(el.duration)) {
      return { duration: NaN, currentTime: NaN, error: el.error?.code ?? 0 };
    }

    el.muted = true;
    const seeked = once('seeked', 8000);
    el.currentTime = seekTo;
    await seeked;
    await el.play();
    await new Promise((r) => setTimeout(r, 600));
    el.pause();

    return { duration: el.duration, currentTime: el.currentTime, error: (el.error as MediaError | null)?.code ?? 0 };
  }, seekTo);
}
