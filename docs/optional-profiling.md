# Optional profiling

New Debug Bar keeps extra collection off unless your app can use it safely.

## Laravel AI activity

If your app has the optional `laravel/ai` package, New Debug Bar adds an **AI activity** section. It records AI work that runs while the current request or runtime profile is active:

- agent, provider, and model
- token usage reported by the provider
- synchronous or streamed completion
- tool names, status, and timing
- start, finish, and incomplete states

It does not add `laravel/ai` to your app. No AI listeners or collector are added when that package is missing. You can turn the section off:

```dotenv
NEWDEBUGBAR_AI_ENABLED=false
```

Prompts, responses, tool arguments, and tool results are hidden by default. To keep them in local profile files and show them in the browser inspector, opt in:

```dotenv
NEWDEBUGBAR_AI_CAPTURE_CONTENT=true
```

Redaction, string limits, array limits, and collector limits still apply. The local MCP server never returns prompt, response, tool argument, or tool result values, even after this setting is enabled.

Queued AI work belongs to its queue runtime profile. It is not attached to the HTTP request that dispatched the job. A streamed AI run appears only after Laravel AI reports that it finished. The panel is a completed activity record, not a live token view.
