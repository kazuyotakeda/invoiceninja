@extends('portal.default.layouts.master')

@section('body')
<main class="main">
    <div class="container-fluid">	
		<div class="row" style="margin-top: 30px;">
			<div class="col-6 col-lg-4">
				<div class="card">
					<div class="card-body p-3 d-flex align-items-center">
							<i class="fa fa-money bg-primary p-3 font-2xl mr-3"></i>
						<div>
							<div class="text-value-sm text-primary">$1.999,50</div>
							<div class="text-muted text-uppercase font-weight-bold small">{{ ctrans('texts.total_invoiced')}}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-6 col-lg-4">
				<div class="card">
					<div class="card-body p-3 d-flex align-items-center">
							<i class="fa fa-hourglass-end bg-info p-3 font-2xl mr-3"></i>
						<div>
							<div class="text-value-sm text-info">$1.999,50</div>
							<div class="text-muted text-uppercase font-weight-bold small">{{ ctrans('texts.paid_to_date') }}</div>
						</div>
					</div>
				</div>
			</div>

			<div class="col-6 col-lg-4">
				<div class="card">
					<div class="card-body p-3 d-flex align-items-center">
							<i class="fa fa-exclamation-triangle bg-warning p-3 font-2xl mr-3"></i>
						<div>
							<div class="text-value-sm text-warning">$1.999,50</div>
							<div class="text-muted text-uppercase font-weight-bold small">{{ ctrans('texts.open_balance')}}</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- update payment methods -->
		<div class="row" style="margin-top: 30px;">
			<div class="col-sm-6 col-lg-3">
				<div class="card text-white bg-warning h-100">
					<div class="card-body">
						<div class="btn-group float-right">
							<button class="btn btn-transparent dropdown-toggle p-0" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
							<i class="fa fa-cog"></i>
							</button>
								<div class="dropdown-menu dropdown-menu-right">
									<a class="dropdown-item" href="#">Remove card</a>
								</div>
						</div>
						<div class="w-50 p-0"><img class="card-img embed-responsive-item" src="/images/visa.png" alt="Visa Card"></div>
						<div>{{ ctrans('texts.expires')}}: 10/20</div>
						<div class="progress progress-white progress-xs my-2">
							<div class="progress-bar" role="progressbar" style="width: 100%" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
						</div>
						<small class="text-value">Mr Joe Citizen - ({{ctrans('texts.default')}})</small>
					</div>
				</div>
			</div>

			<div class="col-sm-6 col-lg-3">
				<div class="card text-white bg-secondary h-100">
					<div class="card-body align-items-center d-flex justify-content-center">
						<button class="btn btn-primary btn-lg"><i class="fa fa-plus" style="" aria-hidden="true"></i> {{ ctrans('texts.add_payment_method')}}</button>
					</div>
				</div>
			</div>
		</div>

    </div>
</main>
</body>
@endsection