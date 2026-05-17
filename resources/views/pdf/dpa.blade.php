<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Data Processing Agreement — {{ $team->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            color: #111827;
            background: #ffffff;
            line-height: 1.6;
        }

        .header {
            background: #0f172a;
            color: #ffffff;
            padding: 24px 32px 20px;
            margin-bottom: 0;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .brand {
            font-size: 18px;
            font-weight: 700;
            color: #60a5fa;
        }
        .brand span { color: #ffffff; }
        .doc-badge {
            background: rgba(96,165,250,0.15);
            border: 1px solid rgba(96,165,250,0.4);
            color: #93c5fd;
            font-size: 8px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
        }
        .doc-title {
            margin-top: 14px;
            font-size: 20px;
            font-weight: 700;
            color: #f1f5f9;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .content {
            padding: 28px 32px;
        }

        .parties-grid {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
        }
        .party-box {
            flex: 1;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 14px 16px;
        }
        .party-label {
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
        .party-name {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .party-detail {
            font-size: 9px;
            color: #374151;
            margin-bottom: 2px;
        }
        .party-detail strong { color: #111827; }

        .section {
            margin-bottom: 18px;
        }
        .section-title {
            font-size: 11px;
            font-weight: 700;
            color: #0f172a;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 4px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .clause {
            margin-bottom: 8px;
        }
        .clause-num {
            font-weight: 700;
            color: #1e40af;
            margin-right: 4px;
        }
        .clause-text {
            font-size: 9.5px;
            color: #374151;
            line-height: 1.55;
        }

        ul.clause-list {
            margin: 6px 0 6px 20px;
            padding: 0;
        }
        ul.clause-list li {
            font-size: 9.5px;
            color: #374151;
            margin-bottom: 3px;
        }

        .highlight-box {
            background: #eff6ff;
            border-left: 3px solid #3b82f6;
            padding: 8px 12px;
            border-radius: 0 4px 4px 0;
            margin: 8px 0;
            font-size: 9.5px;
            color: #1e3a8a;
        }

        .signatures {
            margin-top: 28px;
            display: flex;
            gap: 32px;
        }
        .sig-block {
            flex: 1;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
        }
        .sig-label {
            font-size: 8px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        .sig-name {
            font-size: 10px;
            font-weight: 700;
            color: #111827;
            margin-top: 4px;
        }
        .sig-role {
            font-size: 9px;
            color: #4b5563;
        }
        .sig-date {
            font-size: 9px;
            color: #64748b;
            margin-top: 4px;
        }
        .sig-line {
            margin-top: 32px;
            border-bottom: 1px solid #94a3b8;
            width: 80%;
            height: 1px;
        }

        .footer {
            margin-top: 24px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            font-size: 8px;
            color: #9ca3af;
            text-align: center;
        }
        .page-num { text-align: right; font-size: 8px; color: #9ca3af; }
    </style>
</head>
<body>

<div class="header">
    <div class="header-top">
        <div class="brand">QR<span>-Master</span></div>
        <div class="doc-badge">GDPR Compliant</div>
    </div>
    <div class="doc-title">Data Processing Agreement</div>
    <div class="doc-subtitle">
        Agreement date: {{ \Carbon\Carbon::parse($controller['agreement_date'])->format('d F Y') }}
        &nbsp;·&nbsp; Reference: DPA-{{ $team->id }}-{{ \Carbon\Carbon::parse($controller['agreement_date'])->format('Ymd') }}
    </div>
</div>

<div class="content">

    {{-- PARTIES --}}
    <div class="parties-grid">
        <div class="party-box">
            <div class="party-label">Data Controller</div>
            <div class="party-name">{{ $controller['company_name'] }}</div>
            <div class="party-detail">{{ $controller['company_address'] }}</div>
            <div class="party-detail">{{ $controller['company_postal'] }} {{ $controller['company_city'] }}, {{ $controller['company_country'] }}</div>
            @if($controller['company_vat'])
            <div class="party-detail"><strong>VAT:</strong> {{ $controller['company_vat'] }}</div>
            @endif
            <div class="party-detail" style="margin-top:6px;"><strong>Representative:</strong> {{ $controller['representative_name'] }}</div>
            <div class="party-detail"><strong>Email:</strong> {{ $controller['representative_email'] }}</div>
        </div>
        <div class="party-box">
            <div class="party-label">Data Processor</div>
            <div class="party-name">QR-Master Sp. z o.o.</div>
            <div class="party-detail">ul. Cyfrowa 1</div>
            <div class="party-detail">00-001 Warsaw, Poland</div>
            <div class="party-detail"><strong>VAT:</strong> PL1234567890</div>
            <div class="party-detail" style="margin-top:6px;"><strong>DPO:</strong> dpo@qr-master.app</div>
            <div class="party-detail"><strong>Workspace:</strong> {{ $team->name }}</div>
        </div>
    </div>

    {{-- §1 DEFINITIONS --}}
    <div class="section">
        <div class="section-title">§1 — Definitions</div>
        <div class="clause">
            <span class="clause-text">
                For the purposes of this Agreement, the terms used herein shall have the meanings ascribed to them in the General Data Protection Regulation (EU) 2016/679 (<strong>GDPR</strong>) and the applicable national data protection laws. Key terms include:
            </span>
            <ul class="clause-list">
                <li><strong>"Personal Data"</strong> — any information relating to an identified or identifiable natural person.</li>
                <li><strong>"Processing"</strong> — any operation performed on Personal Data, including collection, storage, use, disclosure, erasure.</li>
                <li><strong>"Controller"</strong> — {{ $controller['company_name'] }}, determining the purposes and means of data processing.</li>
                <li><strong>"Processor"</strong> — QR-Master Sp. z o.o., processing Personal Data on behalf of the Controller.</li>
                <li><strong>"Sub-processor"</strong> — any third party engaged by the Processor to process Personal Data.</li>
            </ul>
        </div>
    </div>

    {{-- §2 SUBJECT & SCOPE --}}
    <div class="section">
        <div class="section-title">§2 — Subject Matter and Scope</div>
        <div class="clause">
            <span class="clause-num">2.1</span>
            <span class="clause-text">The Processor shall process Personal Data exclusively on behalf of and according to the documented instructions of the Controller, for the purposes of providing the QR-Master platform services (dynamic QR code generation, scan analytics, link management).</span>
        </div>
        <div class="clause">
            <span class="clause-num">2.2</span>
            <span class="clause-text">Categories of Personal Data processed: end-user IP addresses (pseudonymized via SHA-256 hashing), device type, browser, approximate geolocation (country/city), scan timestamps.</span>
        </div>
        <div class="clause">
            <span class="clause-num">2.3</span>
            <span class="clause-text">Categories of Data Subjects: individuals who scan QR codes managed through the Controller's workspace on the QR-Master platform.</span>
        </div>
        <div class="clause">
            <span class="clause-num">2.4</span>
            <span class="clause-text">Duration: this Agreement applies for the duration of the service contract between the Controller and the Processor and terminates automatically upon termination of that contract.</span>
        </div>
    </div>

    {{-- §3 OBLIGATIONS OF PROCESSOR --}}
    <div class="section">
        <div class="section-title">§3 — Obligations of the Processor</div>
        <div class="clause">
            <span class="clause-num">3.1</span>
            <span class="clause-text">The Processor shall process Personal Data only on documented instructions from the Controller, including with regard to transfers to third countries, unless required to do so by Union or Member State law to which the Processor is subject.</span>
        </div>
        <div class="clause">
            <span class="clause-num">3.2</span>
            <span class="clause-text">The Processor ensures that persons authorised to process the Personal Data have committed themselves to confidentiality or are under an appropriate statutory obligation of confidentiality.</span>
        </div>
        <div class="clause">
            <span class="clause-num">3.3</span>
            <span class="clause-text">The Processor implements appropriate technical and organisational measures (Article 32 GDPR) including: TLS 1.3 encryption in transit, AES-256 encryption at rest, IP pseudonymisation, role-based access control, regular security audits.</span>
        </div>
        <div class="clause">
            <span class="clause-num">3.4</span>
            <span class="clause-text">The Processor assists the Controller in ensuring compliance with obligations pursuant to Articles 32–36 GDPR (security, DPIA, prior consultation, breach notification).</span>
        </div>
        <div class="clause">
            <span class="clause-num">3.5</span>
            <span class="clause-text">At the choice of the Controller, the Processor shall delete or return all Personal Data upon termination of services and delete existing copies unless storage is required by Union or Member State law.</span>
        </div>
        <div class="clause">
            <span class="clause-num">3.6</span>
            <span class="clause-text">The Processor makes available to the Controller all information necessary to demonstrate compliance with the obligations laid down in Article 28 GDPR and allows for and contributes to audits conducted by the Controller or a mandated auditor, upon 30 days' written notice.</span>
        </div>
    </div>

    {{-- §4 SUB-PROCESSORS --}}
    <div class="section">
        <div class="section-title">§4 — Sub-processors</div>
        <div class="clause">
            <span class="clause-num">4.1</span>
            <span class="clause-text">The Controller hereby grants general written authorisation to the Processor to engage sub-processors. The Processor shall inform the Controller of any intended changes by email to <strong>{{ $controller['representative_email'] }}</strong> at least 14 days in advance, giving the Controller opportunity to object.</span>
        </div>
        <div class="clause">
            <span class="clause-num">4.2</span>
            <span class="clause-text">Current approved sub-processors:</span>
            <ul class="clause-list">
                <li><strong>Cloudflare R2</strong> (Cloudflare Inc., USA) — media asset storage, covered by Standard Contractual Clauses.</li>
                <li><strong>Hetzner Cloud</strong> (Hetzner Online GmbH, Germany/EU) — server infrastructure.</li>
                <li><strong>Stripe</strong> (Stripe Inc., USA) — payment processing, covered by SCCs.</li>
                <li><strong>Sentry</strong> (Functional Software Inc., USA) — error monitoring with PII filtering, covered by SCCs.</li>
            </ul>
        </div>
        <div class="clause">
            <span class="clause-num">4.3</span>
            <span class="clause-text">The Processor shall impose data protection obligations equivalent to those set out in this Agreement on any sub-processor by contract, and remains fully liable for the performance of the sub-processor's obligations.</span>
        </div>
    </div>

    {{-- §5 DATA SUBJECT RIGHTS --}}
    <div class="section">
        <div class="section-title">§5 — Data Subject Rights</div>
        <div class="clause">
            <span class="clause-num">5.1</span>
            <span class="clause-text">The Processor shall assist the Controller in responding to requests from data subjects exercising their rights under Chapter III GDPR (access, rectification, erasure, restriction, portability, objection). The Processor shall forward any such requests to the Controller without undue delay.</span>
        </div>
        <div class="clause">
            <span class="clause-num">5.2</span>
            <span class="clause-text">The geo-location data in scan logs is automatically anonymised after 90 days (city/country set to NULL). IP hashes are never stored in plain text.</span>
        </div>
    </div>

    {{-- §6 SECURITY & BREACH --}}
    <div class="section">
        <div class="section-title">§6 — Security & Data Breach Notification</div>
        <div class="clause">
            <span class="clause-num">6.1</span>
            <span class="clause-text">The Processor shall notify the Controller without undue delay (within 72 hours) after becoming aware of a personal data breach affecting data processed under this Agreement, providing information sufficient to allow the Controller to fulfil its obligations under Article 33 GDPR.</span>
        </div>
        <div class="highlight-box">
            Security contacts: <strong>security@qr-master.app</strong> (general) · <strong>dpo@qr-master.app</strong> (DPO)
        </div>
    </div>

    {{-- §7 GOVERNING LAW --}}
    <div class="section">
        <div class="section-title">§7 — Governing Law & Jurisdiction</div>
        <div class="clause">
            <span class="clause-text">This Agreement shall be governed by and construed in accordance with the laws of the Republic of Poland and the European Union's GDPR. Any dispute arising in connection with this Agreement shall be subject to the exclusive jurisdiction of the courts of Warsaw, Poland.</span>
        </div>
    </div>

    {{-- SIGNATURES --}}
    <div class="signatures">
        <div class="sig-block">
            <div class="sig-label">Data Controller</div>
            <div class="sig-name">{{ $controller['company_name'] }}</div>
            <div class="sig-role">{{ $controller['representative_name'] }}</div>
            <div class="sig-date">Date: {{ \Carbon\Carbon::parse($controller['agreement_date'])->format('d/m/Y') }}</div>
            <div class="sig-line"></div>
            <div class="sig-label" style="margin-top:4px;">Authorised Signature</div>
        </div>
        <div class="sig-block">
            <div class="sig-label">Data Processor</div>
            <div class="sig-name">QR-Master Sp. z o.o.</div>
            <div class="sig-role">Data Protection Officer</div>
            <div class="sig-date">Date: {{ \Carbon\Carbon::parse($controller['agreement_date'])->format('d/m/Y') }}</div>
            <div class="sig-line"></div>
            <div class="sig-label" style="margin-top:4px;">Authorised Signature</div>
        </div>
    </div>

    <div class="footer">
        Generated {{ $generatedAt->format('d M Y H:i') }} UTC · QR-Master DPA ref. DPA-{{ $team->id }}-{{ \Carbon\Carbon::parse($controller['agreement_date'])->format('Ymd') }} · This document is legally binding when signed by both parties.
    </div>

</div>
</body>
</html>
