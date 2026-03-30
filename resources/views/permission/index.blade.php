@extends('layouts.admin')
@section('content')
    <div class="row">
        <div class="col-md-6">
            <h3 class="breadcrumb page-breadcrumb">Permission</h3>
        </div>
        <div class="col-md-6">
            <div class="text-lg-right">
                <a href="{{ url('admin/setting/permission/create') }}" class="btn btn-success btn-sm mr-1"><span><i class="fal fa-plus mr-1"></i> Add New</span></a>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-xl-12">
            <div id="panel-1" class="panel">
                <div class="panel-hdr">
                    <h2>
                        Permission
                    </h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <!-- datatable start -->
                        <table id="dt-basic-example" class="table table-bordered table-hover table-striped w-100">
                            <thead>
                                <tr>
                                    <th>
                                        <div class="custom-control custom-checkbox custom-control-inline big-checkbox">
                                            <input type="checkbox" class="custom-control-input checkAll" name="checkAll" id="checkAll" onClick="toggle(this)">
                                            <label class="custom-control-label" for="checkAll"></label>
                                        </div>
                                    </th>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if (count($data)>0)
                                    @foreach ($data as $key=>$item)
                                        <tr>
                                            <td>
                                                <div class="custom-control custom-checkbox custom-control-inline big-checkbox">
                                                    <input type="checkbox" class="custom-control-input sub_chk" name="checkbox" data-id="{{ $item->id }}" value="{{$item->id}}">
                                                    <label class="custom-control-label" for="{{$item->id}}"></label>
                                                </div>
                                            </td>
                                            <td>{{$item->id}}</td>
                                            <td>{{$item->name}}</td>
                                            <td>{{$item->created_at}}</td>
                                            <td>
                                                <div class="d-flex demo">
                                                    {{-- @can('Permission Delete') --}}
                                                        <a href="javascript:void(0);" class="btn btn-sm btn-outline-danger btn-icon btn-inline-block mr-1 btn_delete" data-id="{{$item->id}}" title="Delete Record"><i class="fal fa-times"></i></a>
                                                    {{-- @endcan --}}
                                                    {{-- @can('Permission Edit') --}}
                                                        <a href="{{url('admin/setting/permission',$item->id)}}/edit" class="btn btn-sm btn-outline-primary btn-icon btn-inline-block mr-1" title="Edit"><i class="fal fa-edit"></i></a>                                                         
                                                    {{-- @endcan --}}
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endif
                            </tbody>
                        </table>
                        <!-- datatable end -->
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    @include('includs.datatable_basic')
    <script>
        $(function(){
            $('.checkAll').on('click', function(e) {
                if($(this).is(':checked',true)){
                    $(".sub_chk:not(:disabled)").prop("checked", true);
                } else {
                    $(".sub_chk:not(:disabled)").prop("checked", false);
                }
            });
            $(document).on('click', '.btn_delete', function () {
                var id = $(this).data("id");
                Swal.fire({
                    title: "Are you sure?",
                    text: "You won't be able to revert this!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#3085d6",
                    cancelButtonColor: "#d33",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.value === true) {
                        $.ajax({
                            url: "{{ url('admin/setting/permission') }}/" + id,
                            type: "POST",
                            data: {
                                _method: "DELETE",
                                _token: "{{ csrf_token() }}"
                            },
                            success: function (response) {
                                Swal.fire("Deleted!", "Your record has been deleted.", "success");
                                location.reload();
                            },
                            error: function (xhr) {
                                console.log(xhr.responseText);
                                Swal.fire("Error!", "Delete failed", "error");
                            }
                        });
                    }
                });
            });
        });
    </script>
@endsection