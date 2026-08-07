# Set up the MCP server

New Debug Bar gives coding agents clear data from saved request profiles. It uses a local [Model Context Protocol (MCP)](https://modelcontextprotocol.io/) server.

Your coding tool starts the server when it needs it. Do not run `mcp:start` in a separate terminal.

## Before you start

- Install New Debug Bar in your Laravel app.
- Make sure the app uses the `local` environment.
- Find the full path to the app's `artisan` file.

The examples below use `/absolute/path/to/your-app/artisan`. Replace it with your real path.

## Codex

Run this command once:

```bash
codex mcp add my-app-debug-bar -- php /absolute/path/to/your-app/artisan mcp:start new-debug-bar
```

Use a name that tells you which app it belongs to. Check the setup with:

```bash
codex mcp list
```

## Claude Code

Run this command from your project:

```bash
claude mcp add --scope local new-debug-bar -- php /absolute/path/to/your-app/artisan mcp:start new-debug-bar
```

Check the setup with:

```bash
claude mcp list
```

## Cursor

Create `.cursor/mcp.json` in your project:

```json
{
  "mcpServers": {
    "new-debug-bar": {
      "command": "php",
      "args": [
        "/absolute/path/to/your-app/artisan",
        "mcp:start",
        "new-debug-bar"
      ]
    }
  }
}
```

## VS Code

Create `.vscode/mcp.json` in your project:

```json
{
  "servers": {
    "new-debug-bar": {
      "type": "stdio",
      "command": "php",
      "args": [
        "/absolute/path/to/your-app/artisan",
        "mcp:start",
        "new-debug-bar"
      ]
    }
  }
}
```

## Other MCP clients

Add a local `stdio` server with this command and these arguments:

```json
{
  "command": "php",
  "args": [
    "/absolute/path/to/your-app/artisan",
    "mcp:start",
    "new-debug-bar"
  ]
}
```

Each client uses a different place or file for this setting. Look for its local MCP or `stdio` server setup.

## Check the connection

Your coding tool should show these four tools:

- `list-debug-profiles`
- `get-debug-profile-section`
- `inspect-debug-queries`
- `get-debug-findings`

Then visit a page in your Laravel app and ask your agent:

> Inspect the latest New Debug Bar profile. Tell me what happened, what looks wrong, and what I should inspect next.

When the agent can read the page's response headers, `X-New-Debug-Bar-Profile` points it to the exact profile. Otherwise, it can find the profile from the recent request list.

## Fix common problems

- **The server is missing:** Make sure the package is installed, the app uses the `local` environment, and `NEW_DEBUG_BAR_ENABLED` is not `false`.
- **The command cannot find PHP:** Replace `php` with the full path to your PHP program.
- **The wrong app opens:** Check that the path points to that app's `artisan` file.
- **No profiles appear:** Visit a page in the app first, then try again.
- **The client only runs online:** This server needs a local client that can start a command on your computer.
