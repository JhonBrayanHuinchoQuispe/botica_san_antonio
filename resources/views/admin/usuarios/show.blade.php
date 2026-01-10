@extends('layout.layout')

@php
    $title = 'Detalles del Usuario';
    $subTitle = 'Información Completa';
@endphp

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Detalles del Usuario</h4>
                        <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">
                            <i class="ri-arrow-left-line"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información Personal</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td>{{ $user->name }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>{{ $user->email }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Teléfono:</strong></td>
                                    <td>{{ $user->telefono ?? 'No especificado' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Cargo:</strong></td>
                                    <td>{{ $user->cargo ?? 'No especificado' }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Estado:</strong></td>
                                    <td>
                                        @if($user->is_active)
                                            <span class="badge bg-success">Activo</span>
                                        @else
                                            <span class="badge bg-danger">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Roles Asignados</h5>
                            @if($user->roles->count() > 0)
                                <div class="list-group">
                                    @foreach($user->roles as $role)
                                        <div class="list-group-item">
                                            <strong>{{ $role->display_name ?? $role->name }}</strong>
                                            @if($role->description)
                                                <br><small class="text-muted">{{ $role->description }}</small>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-muted">No tiene roles asignados</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 