<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>SchoolCanteen API</title>

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background:
                radial-gradient(
                    circle at top right,
                    rgba(59, 130, 246, 0.10),
                    transparent 34%
                ),
                #f6f7f9;
            color: #111827;
            font-family:
                Inter,
                ui-sans-serif,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;
        }

        .page {
            width: 100%;
            max-width: 900px;
        }

        .card {
            overflow: hidden;
            border: 1px solid #e5e7eb;
            border-radius: 24px;
            background: #ffffff;
            box-shadow:
                0 24px 70px rgba(15, 23, 42, 0.08);
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 24px 28px;
            border-bottom: 1px solid #eceff3;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .brand-mark {
            width: 46px;
            height: 46px;
            display: grid;
            place-items: center;
            flex: 0 0 auto;
            border-radius: 14px;
            background: #111827;
            color: #ffffff;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }

        .brand-name {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            font-size: 20px;
            line-height: 1.1;
        }

        .brand-description {
            margin: 4px 0 0;
            color: #6b7280;
            font-size: 12px;
        }

        .status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid #dce8df;
            border-radius: 999px;
            background: #f4faf5;
            color: #166534;
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
        }

        .content {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(260px, 0.65fr);
            gap: 52px;
            padding: 64px 56px;
        }

        .eyebrow {
            margin: 0 0 14px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        h1 {
            max-width: 540px;
            margin: 0;
            color: #0f172a;
            font-family: Georgia, "Times New Roman", serif;
            font-size: clamp(38px, 6vw, 64px);
            font-weight: 700;
            line-height: 0.98;
            letter-spacing: -0.04em;
        }

        .lead {
            max-width: 560px;
            margin: 24px 0 0;
            color: #64748b;
            font-size: 15px;
            line-height: 1.8;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 32px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 0 18px;
            border-radius: 11px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition:
                transform 160ms ease,
                background 160ms ease;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            background: #111827;
            color: #ffffff;
        }

        .button-primary:hover {
            background: #1f2937;
        }

        .button-secondary {
            border: 1px solid #e5e7eb;
            background: #ffffff;
            color: #374151;
        }

        .meta {
            align-self: center;
            border-left: 1px solid #e5e7eb;
            padding-left: 34px;
        }

        .meta-title {
            margin: 0 0 20px;
            color: #111827;
            font-size: 13px;
            font-weight: 700;
        }

        .meta-list {
            display: grid;
            gap: 20px;
            margin: 0;
        }

        .meta-item {
            display: grid;
            gap: 5px;
        }

        .meta-label {
            color: #9ca3af;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .meta-value {
            color: #1f2937;
            font-size: 14px;
            font-weight: 600;
        }

        .online {
            color: #15803d;
        }

        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 18px 28px;
            border-top: 1px solid #eceff3;
            color: #9ca3af;
            font-size: 11px;
        }

        .footer strong {
            color: #64748b;
            font-weight: 600;
        }

        @media (max-width: 720px) {
            body {
                align-items: flex-start;
                padding: 20px 14px;
            }

            .header {
                padding: 20px;
            }

            .status {
                padding: 7px 10px;
            }

            .status-text {
                display: none;
            }

            .content {
                grid-template-columns: 1fr;
                gap: 40px;
                padding: 44px 24px;
            }

            .meta {
                border-top: 1px solid #e5e7eb;
                border-left: 0;
                padding-top: 28px;
                padding-left: 0;
            }

            .meta-list {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer {
                align-items: flex-start;
                flex-direction: column;
                padding: 18px 20px;
            }
        }
    </style>
</head>

<body>
    <main class="page">
        <section class="card">

            <header class="header">
                <div class="brand">
                    <div class="brand-mark">
                        SS
                    </div>

                    <div>
                        <h2 class="brand-name">
                            SakuSekolah
                        </h2>

                        <p class="brand-description">
                            School Commerce Management
                        </p>
                    </div>
                </div>

                <div class="status">
                    <span class="status-dot"></span>

                    <span class="status-text">
                        API Operational
                    </span>
                </div>
            </header>

            <div class="content">
                <div>
                    <p class="eyebrow">
                        Backend Service
                    </p>

                    <h1>
                        SchoolCanteen API
                    </h1>

                    <p class="lead">
                        Layanan backend untuk ekosistem SchoolCanteen,
                        menghubungkan siswa, merchant, dan administrator
                        dalam satu sistem perdagangan sekolah.
                    </p>

                    <div class="actions">
                        <a
                            class="button button-primary"
                            href="/api/health"
                        >
                            Check API Health
                        </a>

                        <a
                            class="button button-secondary"
                            href="/api/v1/products"
                        >
                            Public Products
                        </a>
                    </div>
                </div>

                <aside class="meta">
                    <p class="meta-title">
                        Service Information
                    </p>

                    <div class="meta-list">
                        <div class="meta-item">
                            <span class="meta-label">
                                Status
                            </span>

                            <span class="meta-value online">
                                Online
                            </span>
                        </div>

                        <div class="meta-item">
                            <span class="meta-label">
                                API Version
                            </span>

                            <span class="meta-value">
                                v1
                            </span>
                        </div>

                        <div class="meta-item">
                            <span class="meta-label">
                                Environment
                            </span>

                            <span class="meta-value">
                                Production
                            </span>
                        </div>

                        <div class="meta-item">
                            <span class="meta-label">
                                Access
                            </span>

                            <span class="meta-value">
                                REST API
                            </span>
                        </div>
                    </div>
                </aside>
            </div>

            <footer class="footer">
                <span>
                    SchoolCanteen Backend Service
                </span>

                <span>
                    Powered by <strong>Laravel</strong>
                </span>
            </footer>

        </section>
    </main>
</body>
</html>