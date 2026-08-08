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

## Completed streamed responses

Streamed HTTP responses are not saved by default. To keep their completed request profiles in history, opt in:

```dotenv
NEWDEBUGBAR_CAPTURE_STREAMED=true
```

This works with Laravel and Symfony responses based on `StreamedResponse`, including `stream`, `eventStream`, `streamJson`, `streamDownload`, and streamed Laravel AI responses when those features are available in your Laravel version.

New Debug Bar wraps the existing stream callback without buffering its output. It saves the profile only after the callback has finished and Laravel has terminated the request. Code that runs inside the callback can then appear in the completed profile.

The saved profile does not contain the streamed response body or a measured response size. It does not add the toolbar or the `X-NewDebugBar-Profile` header to the streamed response because headers may already be on their way to the client. Open a later page and use History, or use the local MCP server, to find the profile.

This is completed request profiling. It does not provide live chunk-by-chunk or token-by-token inspection. If the stream callback never finishes, New Debug Bar does not claim a completed profile.
