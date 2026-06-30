<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF to Excel Converter</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            padding: 40px;
            width: 100%;
            max-width: 520px;
        }
        h1 {
            font-size: 1.5rem;
            color: #1e293b;
            margin-bottom: 8px;
        }
        p.subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 28px;
        }
        label {
            display: block;
            font-weight: bold;
            font-size: 0.9rem;
            color: #374151;
            margin-bottom: 8px;
        }
        .file-input-wrapper {
            border: 2px dashed #cbd5e1;
            border-radius: 8px;
            padding: 28px;
            text-align: center;
            margin-bottom: 20px;
            transition: border-color 0.2s;
        }
        .file-input-wrapper:hover { border-color: #2563eb; }
        .file-input-wrapper input[type="file"] {
            display: block;
            width: 100%;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .file-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .file-hint {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 8px;
        }
        button[type="submit"] {
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        button[type="submit"]:hover { background: #1d4ed8; }
        .error-box {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #dc2626;
            font-size: 0.9rem;
        }
        .success-msg {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-radius: 6px;
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #16a34a;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 PDF to Excel</h1>
        <p class="subtitle">Upload a PDF file to extract its content into an Excel spreadsheet.</p>

        @if ($errors->any())
            <div class="error-box">
                @foreach ($errors->all() as $error)
                    <p>⚠️ {{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="success-msg">✅ {{ session('success') }}</div>
        @endif

        <form action="{{ route('pdf-to-excel.convert') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="pdf_file">Select your PDF file</label>
            <div class="file-input-wrapper">
                <div class="file-icon">📂</div>
                <input type="file" name="pdf_file" id="pdf_file" accept=".pdf" required>
                <p class="file-hint">PDF only · Max 10MB</p>
            </div>
            <button type="submit">⬇️ Convert & Download Excel</button>
        </form>
    </div>
</body>
</html>