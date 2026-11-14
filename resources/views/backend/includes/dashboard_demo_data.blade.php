<?php
$user = auth()->user();
$isCleaner = $isCleaner ?? ($user && $user->hasRole(\App\Services\SectorService::CLEANER_ROLE));
$isCustomer = $isCustomer ?? ($user && $user->hasRole('customer') && ! $isCleaner);
?>
@if($isCustomer || $isCleaner)
<div class="row">
@if($isCustomer)
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
    @if($isCleaner)
    <div class="col-sm-12 col-lg-12">
        <h4>Today's Tasks</h4>
        <p class="text-muted">Cars assigned to you that are active or on hold.</p>
        @if($orders->isEmpty())
        <div class="alert alert-warning">You have no cars assigned right now.</div>
        @else
        <table class="table table-bordered table-hover table-responsive-sm">
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Car No</th>
                    <th>Address</th>
                    <th>Cleaning</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orders as $order)
                <tr>
                    <td>{{ $order->user_name }}</td>
                    <td>{{ $order->user_mobile }}</td>
                    <td>{{ $order->car_number }}</td>
                    <td>{{ $order->house_no }}</td>
                    <td>{{ $order->internaltype_name }}</td>
                    <td>{{ $order->office_time }}</td>
                    <td>{{ (int) $order->status === 4 ? 'Hold' : 'Active' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        <a href="{{ route('backend.cleaners.index') }}" class="btn btn-primary">Open Today Task</a>
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
@if(! $isCustomer && ! $isCleaner)
@php
    $dashboardQuery = $canSeeAllSectors
        ? 'filter_franchise_id='.$selectedFranchise
        : 'filter_sector_id='.$selectedSector;
@endphp
<div class="row mb-3">
    <div class="col-sm-4">
        @if($canSeeAllSectors)
        <form method="GET" action="{{ route('backend.dashboard') }}" id="franchise-filter-form">
            <label class="form-label" for="filter_franchise_id">@lang('Franchise')</label>
            <select name="filter_franchise_id" id="filter_franchise_id" class="form-control" onchange="document.getElementById('franchise-filter-form').submit();">
                <option value="*">@lang('All Franchises')</option>
                @foreach($franchiseOptions as $franchise_id => $franchise_name)
                <option value="{{ $franchise_id }}" @if((string) $selectedFranchise === (string) $franchise_id) selected @endif>{{ $franchise_name }}</option>
                @endforeach
            </select>
        </form>
        @else
        <form method="GET" action="{{ route('backend.dashboard') }}" id="sector-filter-form">
            <label class="form-label" for="filter_sector_id">@lang('Sector')</label>
            <select name="filter_sector_id" id="filter_sector_id" class="form-control" onchange="document.getElementById('sector-filter-form').submit();">
                @if(count($sectorOptions) > 1)
                <option value="*">@lang('All My Sectors')</option>
                @endif
                @foreach($sectorOptions as $sector_id => $sector_name)
                <option value="{{ $sector_id }}" @if((string) $selectedSector === (string) $sector_id) selected @endif>{{ $sector_name }}</option>
                @endforeach
            </select>
        </form>
        @endif
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
            <a href="{{ route('backend.orders.index') }}?{{ $dashboardQuery }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalOrders }}</div>
                    <div>Total Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?only_new=true&{{ $dashboardQuery }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalNewOrders }}</div>
                    <div>New Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?expired=true&{{ $dashboardQuery }}">
                <div class="card-body">
                    <div class="fs-4 fw-semibold">{{ $totalExpiredOrders }}</div>
                    <div>Expired Subscritions</div>
                </div>
            </a>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card mb-4 text-white bg-primary">
            <a href="{{ route('backend.orders.index') }}?with_status=4&{{ $dashboardQuery }}">
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