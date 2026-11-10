<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Project Baru</h2>
    </x-slot>

    <div class="py-8 max-w-2xl mx-auto sm:px-6 lg:px-8">
        <form action="{{ route('projects.store') }}" method="POST" class="bg-white p-6 rounded-lg shadow">
            @csrf
            @include('projects._form')
            <div class="mt-6 flex gap-3">
                <button class="px-4 py-2 bg-indigo-600 text-white rounded-md">Simpan</button>
                <a href="{{ route('projects.index') }}" class="px-4 py-2 text-gray-600">Batal</a>
            </div>
        </form>
    </div>
</x-app-layout>