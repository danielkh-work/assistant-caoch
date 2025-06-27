@extends('layouts.main_layout')
@section('content')
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Profile Detail</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="#">Home</a></li>
                        <li class="breadcrumb-item active">Profile Detail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <section style="background-color: #eee;padding:10px">
        <div class="row">
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="{{ $customer->image ? url($customer->image) : url('/assets/img/image-not-found.jpg') }}"
                            title="Image" alt="Image" class="rounded-circle img-fluid" data-toggle="modal"
                            data-target="#image" style="width: 100px;height:100px">
                        <h5 class="my-3">{{ Str::ucfirst($customer->name) }}</h5>
                        <p class="text-muted mb-1">{{ $customer->name }}</p>
                        <div class="d-flex justify-content-center mb-2">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Full Name</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{ $customer->name ?? 'Name not found' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Position</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{ $customer->position==1? 'offence':'deffence' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Number</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{ $customer->number ?? '---' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Speed</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{ $customer->speed ?? '---' }}</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-sm-3">
                                <p class="mb-0">Size</p>
                            </div>
                            <div class="col-sm-9">
                                <p class="text-muted mb-0">{{ $customer->size ?? '---' }}</p>
                            </div>
                        </div>
                        <hr>


                    </div>
                </div>
            </div>
        </div>
        </div>
    </section>

    <!-- Image Modal -->
    <div class="modal fade" id="image" tabindex="-1" role="dialog" aria-labelledby="imageLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageLabel">Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="{{ $customer->image ? url($customer->image) : url('/assets/img/image-not-found.jpg') }}"
                        title="Image" alt="Image" class="mx-auto d-block img-fluid"
                        style="width:300px;height:300px;margin:0 auto;">
                </div>
            </div>
        </div>
    </div>

    @endsection
   
