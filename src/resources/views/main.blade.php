@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
	<div class="row row-cols-1 row-cols-md-1 mb-1 text-center">
		<div class="col">
			<div class="card mb-4 rounded-3 shadow-sm">
				<div class="card-header py-3">
					<h4 class="my-0 fw-normal">SoVest</h4>
				</div>
				<div class="card-body">
					<p>Sign up now to access stock picks from talented individuals and make your own predictions
						to boost that REP score!</p>

					<!-- Displaying Errors -->
					@if ($errors->any())
						<div class="alert alert-danger">
							@foreach ($errors->all() as $error)
								<div>{{ $error }}</div>
							@endforeach
						</div>
					@endif
					<form action="{{ route('login.submit') }}" method="post">
						@csrf

						<div class="form-floating">
							<input type="email" class="form-control" id="email" name="email" required value="{{ old('email') }}">
							<label for="email">Email</label>
						</div>
						<br>

						<!-- SAMPLE PASSWORD FORM (WITH REQUIRED) -->
						<div class="form-floating">
							<input type="password" class="form-control" id="password" name="password" required>
							<label for="password">Password</label>
						</div>
						<br>

						<a href="{{ route('user.home') }}">
							<button class="btn btn-success w-100 py-2" type="submit">Log In</button>
						</a>
					</form>
					<br>
					<br>
					<p>New to SoVest? <a href="{{ url('/register') }}">Sign Up Here!</a></p>

				</div>
			</div>
		</div>
	</div>
@endsection

@push('styles')
	<style>
		.card-body {
			width: 70%;
			padding-left: 30%;
		}
	</style>
@endpush