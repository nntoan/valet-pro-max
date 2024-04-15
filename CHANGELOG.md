# Changelog
All notable changes to valet-pro-max will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [[1.0.0]](https://github.com/weprovide/valet-plus/compare/2.4.2...3.0.0)
### What's changed
- Full rewrite of Valet+/Valet Pro Max with the following major differences between the previous versions.
- No longer a fork of Laravel's Valet, but a toolkit around Valet (valet is now a dependency).
- No more platform dependencies when running commands.
- Use command `valet-pro` instead of `valet` (for now).
- Rename .env.valet to .valet-env.php.
- Use command `valet-pro elasticsearch|es use` instead of `valet-pro use elasticsearch|es` .
- Use 127.0.0.1 as Redis host instead of /tmp/redis.sock.
- Choose which binaries to install (default all) and self-update on `valet-pro install` command.
- Adds dependency on Docker for Elasticsearch, see https://docs.docker.com/desktop/install/mac-install/
- Supports for Opensearch via Homebrew
