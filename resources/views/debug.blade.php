@extends('layouts.template')

@section('title', $title)

@section('content')
    <section style="background-color: #f4645f;">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h1 class="text-center" style="padding: 50px 0">DEBUGING - {{$title}}</h1>
                </div>
            </div>
        </div>
    </section>
    <section style="padding: 35px 0;">
        <div class="container">
            <div class="row">
                <div class="col-sm-3">
                    <h3>Index</h3>
                    <ul class="nav flex-column nav-pills">
                        <?php foreach($index_data as $each_index): ?>
                            <?php $activeClass = isset($debug_data->id) ? $each_index->id == $debug_data->id ? 'active':'':'' ?>
                            <li class="nav-item">
                                <a href="<?php echo '?focus=' . $each_index->id ?>" class="nav-link <?php echo $activeClass ?> <?php echo !$each_index->fwd_status ? 'error':'' ?>"><?php echo $each_index->created_at ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-sm-9">
                    <?php if(!$debug_data): ?>
                        <h1 class="text-center">NO DATA / EXPIRED</h1>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-12">
                                <h1>
                                    <span class="custom-badge <?php echo $debug_data->fwd_status ? 'OK':'ERROR' ?>"><?php echo $debug_data->fwd_status ? 'OK':'ERROR' ?></span>
                                    <?php echo $debug_data->created_at ?>
                                </h1>

                                <p>
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1"><?php echo $debug_data->method ?></span>
                                        </div>
                                        <input type="text" class="form-control" value="<?php echo $fwd_url ?>" readonly />
                                    </div>
                                </p>

                                <p>
                                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#headerCollapse" aria-expanded="false" aria-controls="headerCollapse">
                                        Header
                                    </button>
                                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#responseCollapse" aria-expanded="false" aria-controls="responseCollapse">
                                        Response
                                    </button>
                                    <button class="btn btn-primary" type="button" data-toggle="collapse" data-target="#payloadCollapse" aria-expanded="false" aria-controls="payloadCollapse">
                                        Payload
                                    </button>
                                </p>

                                <div class="collapse" id="headerCollapse">
                                    <div class="card card-body">
                                        <div class="code_block"><?php echo json_encode(json_decode($debug_data->header), JSON_PRETTY_PRINT) ?></div>
                                    </div>
                                </div>

                                <div class="collapse show" id="responseCollapse">
                                    <div class="card card-body">
                                        <div class="code_block"><?php echo $debug_data->fwd_response ?></div>
                                    </div>
                                </div>

                                <div class="collapse" id="payloadCollapse">
                                    <div class="card card-body">
                                        <div class="code_block"><?php echo json_encode(json_decode($debug_data->payload), JSON_PRETTY_PRINT) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </section>

    <style>
        .code_block {
            word-break: break-word;
            white-space: pre-wrap;
            color: #fff;
            padding: 15px;
            background-color: #000;
        }

        .custom-badge {
            display: inline-block;
            margin-right: 15px;
            padding: 5px 10px;
            color: white;
        }

        .custom-badge.OK {
            background-color: green;
        }

        .custom-badge.ERROR {
            background-color: red;
        }

        .nav-link.error {
            color: #ff0000;
        }
    </style>
@endsection
