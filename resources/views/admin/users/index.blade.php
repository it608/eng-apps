@extends('layouts.admin')


@section('content')
<div class="bg-white p-6 rounded shadow">

    <div class="flex justify-between mb-4">
        <h2 class="text-xl font-bold">User Management</h2>
        <a href="/admin/users/create" class="bg-blue-600 text-white px-4 py-2 rounded">
            + Add User
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-2 rounded mb-3">
            {{ session('success') }}
        </div>
    @endif

<table class="w-full border border-gray-200 rounded overflow-hidden">
    <thead class="bg-gray-50 text-sm text-gray-600">
        <tr>
            <th class="p-3 border">Name</th>
            <th class="p-3 border">Email</th>
            <th class="p-3 border">Role</th>
            <th class="p-3 border text-center">Action</th>
        </tr>
    </thead>

    <tbody class="text-sm">
    @foreach($users as $user)
        <tr class="hover:bg-gray-50">
            <td class="p-3 border">{{ $user->name }}</td>
            <td class="p-3 border">{{ $user->email }}</td>
            <td class="p-3 border">
                <span class="px-2 py-1 rounded text-xs
                    {{ $user->role === 'admin' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">
                    {{ ucfirst($user->role) }}
                </span>
            </td>
            <td class="p-3 border text-center">
                <a href="/admin/users/{{ $user->id }}/edit"
                   class="text-blue-600 hover:underline mr-3">
                    Edit
                </a>

                <form action="/admin/users/{{ $user->id }}"
                      method="POST"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button class="text-red-600 hover:underline"
                        onclick="return confirm('Delete user?')">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>

</div>
@endsection
