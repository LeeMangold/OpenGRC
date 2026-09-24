<p align="center">
  <img src="https://og-website.nyc3.digitaloceanspaces.com/image/logos/logo-256.png" alt="OpenGRC" width="160" />
</p>

<h1 align="center">OpenGRC</h1>

<p align="center">
  <strong>GRC anyone can do.</strong><br>
  Self-hosted governance, risk and compliance for small and mid-sized security teams.
</p>

<p align="center">
  <a href="https://opengrc.com">Website</a> ·
  <a href="https://docs.opengrc.com">Documentation</a> ·
  <a href="https://opengrc.com/community">Community Edition</a> ·
  <a href="https://opengrc.com/enterprise">Enterprise</a> ·
  <a href="https://trust.opengrc.com">Trust Center</a>
</p>

---

OpenGRC is a governance, risk and compliance platform built for teams who need to run a
real security program without a six-figure budget or a three-week onboarding. It is not
trying to replace a large enterprise GRC suite — but for a lot of organisations, it does.

We built it because we were tired of platforms that cost $30k a year, required a
consultant to operate, and still nickel-and-dimed on every module.

**Self-host it for free. Run your whole program. Own your data.**

## What it does

- **Frameworks and controls** — import common security frameworks, then connect standards,
  controls and your actual implementations rather than maintaining three spreadsheets.
- **Risk management** — a risk register that links to the controls and assets it actually
  concerns, with scoring and treatment tracking.
- **Audit management** — run internal and external audits end to end: requests, evidence,
  findings and remediation, with reports you can hand to an auditor.
- **Vendor risk (TPRM)** — vendor inventory, security questionnaires and document tracking.
- **Incident response** — incidents, playbooks, tasks and timelines.
- **Policy management** — author, review and publish policies with approval workflows.
- **Asset and application inventory** — know what you have before you try to secure it.
- **Dashboards and reporting** — progress you can show a board without building a deck.

## Requirements

- PHP 8.2+
- MySQL / MariaDB (or PostgreSQL)
- Composer and Node.js

Built on [Laravel](https://laravel.com) and [Filament](https://filamentphp.com).

## Quick start

The fastest path is Docker:

```bash
git clone https://github.com/LeeMangold/OpenGRC.git
cd OpenGRC
cp .env.docker.example .env
docker compose up -d
```

To install directly onto a server instead:

```bash
git clone https://github.com/LeeMangold/OpenGRC.git
cd OpenGRC
./install.sh
```

Full installation, upgrade and configuration guides live at
**[docs.opengrc.com](https://docs.opengrc.com)**.

## Community and Enterprise

The **Community Edition** in this repository is the complete self-hosted platform. No seat
limits, no data caps, no crippled trial. It covers the core program: risk, controls and
implementations, vendor management, project management and incident response.

**[Enterprise](https://opengrc.com/enterprise)** is the hosted, supported edition. It adds
the AI and premium modules — AI risk assessments, AI gap assessments, automated vendor
survey responses — along with an **MCP server** that connects OpenGRC directly to Claude
and ChatGPT, so you can ask your assistant which controls lack evidence and have it
actually read your program. It comes with an uptime commitment, SOC 2 Type II attestation
and support. See [pricing](https://opengrc.com/pricing) and
[security](https://opengrc.com/security).

Running GRC for a book of clients? There is an [MSSP edition](https://opengrc.com/mssp)
with isolated instances per client.

## Contributing

Contributions are welcome — issues, pull requests and ideas alike. Start with
[CONTRIBUTING.md](CONTRIBUTING.md), and please open an issue before starting anything
large so we can agree on the approach first.

Questions and ideas are best raised in
[Discussions](https://github.com/LeeMangold/OpenGRC/discussions).

## Security

Please do not report security issues in public issues. See [SECURITY.md](SECURITY.md) for
how to report a vulnerability, or use the contact details published at
[opengrc.com/.well-known/security.txt](https://opengrc.com/.well-known/security.txt).

## License

OpenGRC is **source-available** under the [Elastic License 2.0](LICENSE.txt).

In plain terms, you may:

- ✅ Use OpenGRC for free, including inside a commercial business
- ✅ Self-host it on your own infrastructure, for your own organisation
- ✅ Read, modify and redistribute the source

What you may **not** do:

- ❌ Offer OpenGRC to third parties as a hosted or managed service
- ❌ Circumvent the licence key functionality
- ❌ Remove or obscure licensing and copyright notices

If you want to provide OpenGRC as a service to your own clients, that is what the
[MSSP edition](https://opengrc.com/mssp) is for — [talk to
us](https://opengrc.com/contact).

> **Licence history.** Commits prior to 14 April 2025 were released under the MIT License.
> Commits from 14 April 2025 were released under CC BY-NC-SA 4.0. Those releases remain
> available under their original terms; the Elastic License 2.0 applies from the date noted
> in this file forward.

OpenGRC™ is a trademark of OpenGRC, LLC.
