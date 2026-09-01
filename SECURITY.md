# Security Policy

## Supported versions

Security fixes are provided for the latest released minor version of
`laranail/demo-mode`. Please keep your installation up to date.

## Reporting a vulnerability

If you discover a security vulnerability, **please do not open a public
issue**. Instead, email **security@simtabi.com** with:

- a description of the vulnerability and its impact,
- steps to reproduce (proof of concept if possible),
- the affected version(s).

You will receive an acknowledgement within a few business days. We will work
with you to validate the issue, prepare a fix, and coordinate a disclosure
timeline. Please give us a reasonable window to release a fix before any public
disclosure.

> **Prefer GitHub private vulnerability reporting** when you can: open it from this
> repository's Security tab. The report arrives attached to the repo with a draft advisory
> and a CVE request path already in place. Email is the fallback for anyone who would
> rather not use GitHub.

## Demo-specific note

Demo mode is a guard layer, not a security boundary. For a public demo you
should still scope database credentials to least privilege, isolate the demo
environment, and never expose production data. The strict connection guard and
read-only connection strategy harden — but do not replace — proper environment
isolation.
