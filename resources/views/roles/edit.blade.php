@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Roles</h3>
        </div>
    </div>
    
    <div id="panel-1" class="panel">
        <div class="panel-hdr">
            <h2>
                Roles
            </h2>
        </div>
        <div class="row">
            <div class="col-xl-12">
                <div class="panel-container">
                    <form action="{{ url('admin/setting/role',$role->Role) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="panel-content">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">ID</label>
                                        <input type="text" class="form-control" name="id" id="id" value="{{$role->Role}}" placeholder="ID" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="">Role</label>
                                        <input type="text" class="form-control" name="role" id="role" value="{{$role->Description}}" placeholder="Role" readonly>
                                    </div>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <div class="frame-wrap">
                                    <div class="custom-control custom-checkbox custom-control-inline">
                                        <input type="checkbox" class="custom-control-input" name="" id="defaultInline" value="" onClick="toggle(this)">
                                        <label class="custom-control-label" for="defaultInline">Check All Permission</label>
                                    </div>
                                </div>
                            </div>
                            <label for="">Permission Name</label>
                            <div class="row">
                                @foreach ($category as $key=>$cate)
                                    <?php
                                        $permission = App\Models\Permission::where('category_id', $cate->id)->get();
                                        // Check if all permissions in the category are checked
                                        $allChecked = $permission->every(function($permis) use ($rolePermission) {
                                            return in_array($permis->id, $rolePermission);
                                        });
                                    ?>
                                    <div class="col-md-3 mb-2">
                                        <div class="form-group">
                                            <div class="card border-success draggable" draggable="true">
                                                <div class="card-header border-success">{{$cate->name}}</div>
                                                <div class="card-body">
                                                    <div class="mb-1">
                                                        <div class="custom-control custom-checkbox custom-control-inline">
                                                            <input type="checkbox" class="custom-control-input check_all check_all_{{$cate->id}}" id="checkAll_{{$cate->id}}" {{ $allChecked ? 'checked' : '' }} onClick="toggle_{{ $cate->id }}(this)">
                                                            <label class="custom-control-label" for="checkAll_{{$cate->id}}">Check All</label>
                                                        </div>
                                                    </div>
                                                    @foreach ($permission as $item)
                                                       <div class="mb-1">
                                                            <div class="custom-control custom-checkbox custom-control-inline">
                                                                <input type="checkbox" name="permission[]" class="custom-control-input check_all ch_all_{{ $cate->id }}" onClick="togglePermis({{count($permission)}}, {{$cate->id}})" id="defaultInline_{{ $item->id }}" value="{{ $item->id }}" {{ in_array($item->id, $rolePermission) ? 'checked' : '' }}>
                                                                <label class="custom-control-label" for="defaultInline_{{ $item->id }}">{{$item->name}}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        function toggle_{{ $cate->id }}(source) {
                                            checkboxes = $('.ch_all_{{ $cate->id }}');
                                            for (var i = 0, n = checkboxes.length; i < n; i++) {
                                                checkboxes[i].checked = source.checked;
                                            }
                                        }
                                    </script>
                                @endforeach
                            </div>
                            <hr>
                            <div class="text-right">
                                <button class="btn btn-danger waves-effect waves-themed" type="submit">Submit</button>
                                <a class="btn btn-secondary waves-effect waves-themed"  href="{{url('admin/setting/role')}}"  type="button">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function() {
            // JavaScript for drag-and-drop functionality
            const draggables = document.querySelectorAll('.draggable');
            const containers = document.querySelectorAll('.col-md-3');

            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', () => {
                    draggable.classList.add('dragging');
                });

                draggable.addEventListener('dragend', () => {
                    draggable.classList.remove('dragging');
                });
            });

            containers.forEach(container => {
                container.addEventListener('dragover', e => {
                    e.preventDefault();
                    const draggingElement = document.querySelector('.dragging');
                    container.appendChild(draggingElement);
                });
            });
        });
        function toggle(source) {
            checkboxes = $('.check_all');
            for (var i = 0, n = checkboxes.length; i < n; i++) {
                checkboxes[i].checked = source.checked;
            }
        }
        function togglePermis(permission, id) {
            let totalChecked = $('.ch_all_' + id + ':checked').length;
            if (permission === totalChecked) {
                $('.check_all_'+id).prop("checked", true);
            } else {
                $('.check_all_'+id).prop("checked", false);
            }
        }
    </script>
@endsection