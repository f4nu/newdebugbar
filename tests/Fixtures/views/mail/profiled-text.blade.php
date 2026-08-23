{{ $heading }}

{{ $messageCopy }}
@if ($detailLabel && $detailValue)
    {{ $detailLabel }}: {{ $detailValue }}
@endif
@if ($actionLabel)
    {{ $actionLabel }}: https://northstar.example.test
@endif

— Northstar
