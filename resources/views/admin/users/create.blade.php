@extends('layouts.admin')


@section('content')
<div class="bg-white p-6 rounded shadow max-w-lg">

    <h2 class="text-xl font-bold mb-4">Create User</h2>

    <form method="POST" action="/admin/users">
        @csrf

        <input name="name" placeholder="Name" class="border p-2 w-full mb-3">

        <input name="email" placeholder="Email" class="border p-2 w-full mb-3">

        <input type="password" name="password"
            placeholder="Password" class="border p-2 w-full mb-3">

        <select name="role" class="border p-2 w-full mb-4">
            <option value="user">User</option>
            <option value="approval">Approval</option>
            <option value="admin">Admin</option>
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Save
        </button>
    </form>

</div>
@endsection
