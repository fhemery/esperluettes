# Multi-edit — chapter switch block — request

*Written by the user. Free form, may be three lines. Everything below is
optional prompting, not a form to fill.*

## What I want

Multi-edit: create a new component block that enables to switch to another chapter of the story.

The aim is to be able to create a "Story where you are the hero", enabling the user to choose the next action interactively. Currently, users are adding links plainly, but having nice buttons leading to the chapters that could be placed anywhere (like the image block is currently possible) would be awesome.

## Why

Plain links are awkward for choose-your-own-adventure / interactive stories. Nice buttons to chapters, placeable anywhere in the multi-edit content, would make that genre workable.

## Constraints or ideas I already have

The feature is a bit tricky from an architectural point of view, because this block is clearly a "story only" block. It makes no sense in static pages, or news, or anywhere else the multi-edit is used. We must therefore find a way to plug new blocks dynamically into the architecture.

## Explicitly out of scope

—
