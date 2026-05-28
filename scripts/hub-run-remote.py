#!/usr/bin/env python3
"""Run a local script on hub via SSH (stdin), avoiding PowerShell quoting."""
from __future__ import annotations

import sys
from pathlib import Path

import paramiko


def main() -> int:
    if len(sys.argv) < 2:
        print("usage: hub-run-remote.py <local-script-path>", file=sys.stderr)
        return 2

    script_path = Path(sys.argv[1]).resolve()
    if not script_path.is_file():
        print(f"missing script: {script_path}", file=sys.stderr)
        return 2

    repo = script_path
    while repo.name != "mainServer" and repo.parent != repo:
        repo = repo.parent
    hub_key = repo.parent / "доступы0" / "ssh-key-1775213440930"
    if not hub_key.is_file():
        print(f"missing hub key: {hub_key}", file=sys.stderr)
        return 2

    script = script_path.read_text(encoding="utf-8")
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        "158.160.252.139",
        username="oblik",
        key_filename=str(hub_key),
        timeout=25,
        allow_agent=False,
        look_for_keys=False,
    )
    _, stdout, stderr = client.exec_command("bash -s", timeout=120)
    stdout.channel.send(script.encode())
    stdout.channel.shutdown_write()
    out = stdout.read().decode("utf-8", "replace")
    err = stderr.read().decode("utf-8", "replace")
    code = stdout.channel.recv_exit_status()
    if out:
        sys.stdout.buffer.write(out.encode("utf-8", "replace"))
    if err.strip():
        sys.stderr.buffer.write(err.encode("utf-8", "replace"))
    client.close()
    return code


if __name__ == "__main__":
    raise SystemExit(main())
