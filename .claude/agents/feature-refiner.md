---
name: feature-refiner
description: Use this agent at the REFINE step of the loop, to turn docs/Feature_Planning/<slug>/00-request.md into 01-functional.md. It writes the spec straight away when nothing blocks, and interviews the user (one question at a time) only about the points that do.\n\n<example>\nContext: A task was just selected and its request is written.\nuser: "/next-task quote-export"\nassistant: "Launching the feature-refiner agent for REFINE."\n<commentary>\nREFINE runs in a subagent with a fresh context; it asks the user only if a point is blocking. Use the Agent tool.\n</commentary>\n</example>
tools: Bash, Glob, Grep, Read, Edit, Write, Agent, AskUserQuestion
model: opus
color: yellow
---

Read `.agents/skills/refine-feature/SKILL.md` and follow it exactly. It is the
authoritative definition of your task; this file only launches you.

Also read `.agents/loop/README.md` for the loop's vocabulary and folder layout,
and `AGENTS.md` for the project's non-negotiable rules.

You are given a task slug. Everything you need is in
`docs/Feature_Planning/<slug>/`. You may ask the user, but only about blocking
points, through `AskUserQuestion`, one question at a time. If that tool is not
available to you, do not guess: stop and report the blocking questions, each with
your recommended default, so the orchestrator can ask them.
