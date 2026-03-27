@extends('layouts.admin')
@section('content')
    <div id="panel-1" class="panel">
    <div class="panel-hdr">
        <h2>
            Add New Module Permission
        </h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <form action="{{ url('admin/setting/permission/'.$data->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="row mb-2">
                    <div class="col-sm-12 col-md-12">
                        <div class="form-group">
                            <label for="">Permission Category</label>
                            <select id="category_id" name="category_id" class="form-control select2 @error('category_id') is-invalid @enderror">
                                <option value="">-- Select --</option>
                                @foreach($category as $item)
                                    <option value="{{$item->id}}" {{ $item->id == $data->category_id ? 'selected' :'' }} >{{$item->name}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @php
                    $name = $data->name;
                @endphp
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <div class="form-group">
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input" onClick="toggle(this)" id="all">
                                    <label class="custom-control-label" for="all">All</label>
                                </div>
                            </div>
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input check_all" name="permission" id="view" value="View" {{ str_contains($name, 'View') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="view">View</label>
                                </div>
                            </div>
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input check_all" name="permission" id="create" value="Create" {{ str_contains($name, 'Create') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="create">Create</label>
                                </div>
                            </div>
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input check_all" name="permission" id="edit" value="Edit" {{ str_contains($name, 'Edit') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="edit">Edit</label>
                                </div>
                            </div>
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input check_all" name="permission" id="delete" value="Delete" {{ str_contains($name, 'Delete') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="delete">Delete</label>
                                </div>
                            </div>
                            <div class="mb-1">
                                <div class="custom-control custom-checkbox custom-control-inline">
                                    <input type="checkbox" class="custom-control-input check_all" name="permission" id="import" value="Import" {{ str_contains($name, 'Import') ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="import">Import</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-12">
                        <div class="wrapper_lan"></div>
                        <button class="add_fields btn btn-success"><i class="fal fa-plus-circle"></i> Add</button>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" value="{{$data->id}}" name="id">
                    <a href="{{url('admin/setting/permission')}}" class="btn btn-secondary waves-effect waves-themed"><span>Back</span></a>
                    <button type="submit" class="btn btn-primary waves-effect waves-themed">Submit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
@section('script')
    <script>
        function toggle(source) {
            checkboxes = $('.check_all');
            for(var i=0, n=checkboxes.length;i<n;i++) {
                checkboxes[i].checked = source.checked;
            }
        }

        //Add Input Fields
        $(document).ready(function() {
            var max_fields = 50; //Maximum allowed input fields
            var x = 1; //Initlal input field is set to 1
            //When user click on add input button
            $(".add_fields").click(function(e){
                e.preventDefault();
                //Check maximum allowed input fields
                if(x < max_fields){
                    x++; //input field increment
                    var text =
                        ' <div class="row">'+
                        ' <div class="col-md-4" style="margin-bottom: 10px;">'+
                        '<input type="text" name="permission[]" class="form-control" placeholder="Pleas enter name ..." required>'+
                        ' </div>'+
                        ' <a href="javascript:void(0);" class="remove_field  btn btn-danger" style="height: 36px;"><i class="fal fa-trash-alt"></i></a>' +
                        ' <div class="col-md-6"></div></div>';
                    //add input field
                    $(".wrapper_lan").append(text);
                }
            });

            //when user click on remove button
            $(".wrapper_lan").on("click",".remove_field", function(e){
                e.preventDefault();
                $(this).parent('div').remove(); //remove inout field
                x--; //inout field decrement
            })
            $(".wrapper_att").on("click",".remove_field", function(e){
                e.preventDefault();
                $(this).parent('div').remove(); //remove inout field
                x--; //inout field decrement
            })
        });
    </script>
@endsection