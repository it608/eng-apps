@extends('layouts.admin')

@section('content')
<div class="bg-white p-6 rounded shadow max-w-lg">

    <h2 class="text-xl font-bold mb-4">Edit User</h2>

    <form method="POST" action="/admin/users/{{ $user->id }}">
        @csrf
        @method('PUT')

        <input name="name" value="{{ $user->name }}"
            class="border p-2 w-full mb-3">

        <input name="email" value="{{ $user->email }}"
            class="border p-2 w-full mb-3">

        <select name="role" class="border p-2 w-full mb-4">
            <option value="user" @selected($user->role === 'user')>User</option>
            <option value="admin" @selected($user->role === 'admin')>Admin</option>
            <option value="approval" @selected($user->role === 'approval')>Approval</option>
        </select>

        <button class="bg-blue-600 text-white px-4 py-2 rounded">
            Update
        </button>
    </form>

</div>
@endsection
