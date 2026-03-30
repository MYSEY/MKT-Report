@extends('layouts.admin')
<style>
    body {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100vh;
        background-color: #f4f4f4;
        font-family: Arial, sans-serif;
    }
    .container {
        text-align: center;
    }
    .icon {
        font-size: 80px;
        color: #f8c42a;
    }
    .message {
        font-size: 24px;
        margin-top: 20px;
        color: #333;
    }
    .request-btn {
        margin-top: 20px;
    }
    .request-btn a {
        display: inline-block;
        padding: 10px 20px;
        background-color: #0073e6;
        color: white;
        text-decoration: none;
        border-radius: 5px;
        font-size: 16px;
    }
    .request-btn a:hover {
        background-color: #005bb5;
    }
</style>
@section('content')
    <div class="container">
        <div class="icon">🔒</div>
        <div class="message">
            You don't have permission to view this page.<br>
            We're sorry, your account does not have perssion to access this page.
        </div>
    </div>
@endsection



