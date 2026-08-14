<?php
$user = auth()->user();
$roles = !empty($user)?$user->roles()->pluck('name')[0]:'';
?>
@if($roles=='customer' || $roles=='cleaner')
<div class="row">
@if($roles=='customer')
    <div class="col-sm-12 col-lg-12">
        <div class="text-center"><a href="{{ route('backend.orders.create') }}" class="btn btn-primary">Add New Car</a></div>
        <h4>Your Orders</h4>
        <table class="table table-bordered table-hover table-responsive-sm dataTable no-footer">
            <thead>
                <tr>
                    <th scope="col">Order Number</th>
                    <th scope="col">Car No</th>
                    <th scope="col">Subscription Start Date</th>
                    <th scope="col">Subscription End Date</th>
                    <!-- <th scope="col">Your Current Cloth Counts</th> -->
                    <th scope="col">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ $order->car_number }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->start_date)->format('d-m-Y') }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->renew_date)->format('d-m-Y') }}</td>
                    <!-- <td>{{ $order->cloth_count }}</td> -->
                    <td>
                        <a href="{{ route('backend.orders.edit',$order->id) }}" class="btn btn-primary">Renew</a>
                        <!-- <a href="{{ route('backend.orders.topUp',$order->id) }}" class="btn btn-primary">Top-up Cloths</a> -->
                        <a href="{{ route('backend.orders.show',$order->id) }}" class="btn btn-primary">view</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    <!-- <div class="col-sm-12 col-lg-12">
        <h4>Recent Activity</h4>
        <table class="table table-bordered table-hover table-responsive-sm dataTable no-footer">
            <thead>
                <tr>
                    <th scope="col">Date</th>
                    <th scope="col">Car No</th>
                    <th scope="col">Cleaner Remark</th>
                    <th scope="col">Customer Remark</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>04-11-2023</td>
                    <td>{{ $order->car_number }}</td>
                    <td>Car Washed</td>
                    <td>Good Job Rahul</td>
                </tr>
                <tr>
                    <td>03-11-2023</td>
                    <td>{{ $order->car_number }}</td>
                    <td>Car Washed</td>
                    <td>Good Work!</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div> -->
    <!-- <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}">
                <div class="card-body">
                    <div></div>
                    <div class="fs-4 fw-semibold">View Orders</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}">
                <div class="card-body">
                    <div>Plan Expiry Date</div>
                    <div class="fs-4 fw-semibold">03-02-2024</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}">
                <div class="card-body">
                    <div></div>
                    <div class="fs-4 fw-semibold">Renew</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.create') }}">
                <div class="card-body">
                    <div></div>
                    <div class="fs-4 fw-semibold">Place New Order</div>
                </div>
            </a>
        </div>
    </div> -->
</div>
@endif
@if($roles!='customer' && $roles!='cleaner')
<div class="row mb-3">
    <div class="col-sm-4">
        <form method="GET" action="{{ route('backend.dashboard') }}" id="sector-filter-form">
            <label class="form-label" for="filter_sector_id">@lang('Sector')</label>
            <select name="filter_sector_id" id="filter_sector_id" class="form-control" onchange="document.getElementById('sector-filter-form').submit();">
                @if($canSeeAllSectors || count($sectorOptions) > 1)
                <option value="*">{{ $canSeeAllSectors ? __('All Sectors') : __('All My Sectors') }}</option>
                @endif
                @foreach($sectorOptions as $sector_id => $sector_name)
                <option value="{{ $sector_id }}" @if((string) $selectedSector === (string) $sector_id) selected @endif>{{ $sector_name }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>
<div class="row">
    <!-- <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.users.index') }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{$totalCustomers}}</div>
                    <div>Total Customer</div>
                </div>
            </a>
        </div>
    </div> -->
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.users.index') }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalCleaner }}</div>
                    <div>Total Cleaner</div>
                </div>
            </a>
        </div>
    </div>
    <!-- <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalOrders }}</div>
                    <div>Total Subscritions</div>
                </div>
            </a>
        </div>
    </div> -->
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?filter_sector_id={{ $selectedSector }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalOrders }}</div>
                    <div>Total Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?only_new=true&filter_sector_id={{ $selectedSector }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalNewOrders }}</div>
                    <div>New Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?expired=true&filter_sector_id={{ $selectedSector }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalExpiredOrders }}</div>
                    <div>Expired Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?with_status=4&filter_sector_id={{ $selectedSector }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalHoldOrders }}</div>
                    <div>Hold Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <div class="card-body">
                <div class="fs-4 fw-semibold">&#8377;{{ number_format($totalRevenue, 2) }}</div>
                <div>Total Revenue</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <div class="card-body">
                <div class="fs-4 fw-semibold">&#8377;{{ number_format($monthRevenue, 2) }}</div>
                <div>Revenue This Month</div>
            </div>
        </div>
    </div>
    <!-- <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?clothexpired=true">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $expiredClothcount }}</div>
                    <div>Cloth Count < 5</div>
                </div>
            </a>
        </div>
    </div> -->
</div>
@endif
<style>
    .text-white a {
        color: #fff !important;
    }
</style>
<!-- /.row-->