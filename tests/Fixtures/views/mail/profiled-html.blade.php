<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ $heading }}</title>
</head>
<body style="margin: 0; background: #f4f4f5; color: #27272a; font-family: Arial, sans-serif">
    <table
        role="presentation"
        width="100%"
        cellspacing="0"
        cellpadding="0"
        style="background: #f4f4f5; padding: 32px 16px"
    >
        <tr>
            <td align="center">
                <table
                    role="presentation"
                    width="600"
                    cellspacing="0"
                    cellpadding="0"
                    style="
                        max-width: 600px;
                        overflow: hidden;
                        border: 1px solid #e4e4e7;
                        border-radius: 14px;
                        background: #ffffff;
                    "
                >
                    <tr>
                        <td
                            style="
                                padding: 24px 28px 18px;
                                border-bottom: 1px solid #e4e4e7;
                                font-size: 17px;
                                font-weight: 700;
                                letter-spacing: -0.2px;
                            "
                        >
                            Northstar
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px 28px">
                            <h1 style="margin: 0; color: #18181b; font-size: 26px; line-height: 1.25">
                                {{ $heading }}
                            </h1>
                            <p style="margin: 16px 0 0; color: #52525b; font-size: 15px; line-height: 1.65">
                                {{ $messageCopy }}
                            </p>

                            @if ($detailLabel && $detailValue)
                                <table
                                    role="presentation"
                                    width="100%"
                                    cellspacing="0"
                                    cellpadding="0"
                                    style="margin-top: 24px; border-radius: 10px; background: #f4f4f5"
                                >
                                    <tr>
                                        <td style="padding: 16px; color: #71717a; font-size: 13px">
                                            {{ $detailLabel }}
                                        </td>
                                        <td
                                            align="right"
                                            style="padding: 16px; color: #18181b; font-size: 14px; font-weight: 700"
                                        >
                                            {{ $detailValue }}
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if ($actionLabel)
                                <table role="presentation" cellspacing="0" cellpadding="0" style="margin-top: 26px">
                                    <tr>
                                        <td style="border-radius: 9px; background: #4f46e5">
                                            <a
                                                href="https://northstar.example.test"
                                                style="
                                                    display: inline-block;
                                                    padding: 12px 18px;
                                                    color: #ffffff;
                                                    font-size: 14px;
                                                    font-weight: 700;
                                                    text-decoration: none;
                                                "
                                            >{{ $actionLabel }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 18px 28px; border-top: 1px solid #e4e4e7; color: #a1a1aa; font-size: 12px">
                            Sent by Northstar in Kyoto, Japan
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
