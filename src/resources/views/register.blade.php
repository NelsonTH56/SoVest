@extends('layouts.app')

@section('title', 'Create Account')

@section('content')
	<div class="row row-cols-1 row-cols-md-1 mb-1 text-center">

		<div class="col">
			<div class="card mb-4 rounded-3 shadow-sm">
				<div class="card-header py-3">
					<h4 class="my-0 fw-normal">New User Registration</h4>
				</div>
				<div class="card-body">

					<!-- Displaying Errors -->
					@if ($errors->any())
						<div class="alert alert-danger">
							@foreach ($errors->all() as $error)
								<div>{{ $error }}</div>
							@endforeach
						</div>
					@endif

					<!-- CUSTOMIZE THIS SECTION WITH FORM INFO -->

					<form action="{{ route('register.submit') }}" method="post">
						@csrf

						<div class="row mb-3">
							<div class="col-md-6">
								<div class="form-floating">
									<input type="text" class="form-control" id="firstName" name="firstName" required
										value="{{ old('firstName') }}">
									<label for="firstName">First Name</label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating">
									<input type="text" class="form-control" id="lastName" name="lastName" required
										value="{{ old('lastName') }}">
									<label for="lastName">Last Name</label>
								</div>
							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-12">
								<div class="form-floating">
									<input type="email" class="form-control" id="newEmail" name="newEmail" required
										value="{{ old('newEmail') }}">
									<label for="newEmail">Email</label>
								</div>
							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-6">
								<div class="form-floating">
									<input type="password" class="form-control" id="newPass" name="newPass" required>
									<label for="newPass">Password</label>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-floating">
									<input type="password" class="form-control" id="confirmPass" name="confirmPass"
										required>
									<label for="confirmPass">Confirm Password</label>
								</div>
							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-12">
								<div class="form-floating">
									<select class="form-control" id="newMajor" name="newMajor">
										<option value="animalScience">Animal Science</option>
										<option value="business">Business</option>
										<option value="computerScience">Computer Science</option>
										<option value="creativeTechnologies">Creative Technologies</option>
										<option value="education">Education</option>
										<option value="economics">Economics</option>
										<option value="humanities">Humanities</option>
									</select>
									<label for="newMajor">Current Major</label>
								</div>
							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-12">
								<div class="form-floating">
									<select class="form-control" id="newYear" name="newYear">
										<option value="freshman">Freshman</option>
										<option value="sophomore">Sophomore</option>
										<option value="junior">Junior</option>
										<option value="senior">Senior</option>
									</select>
									<label for="newYear">Current Grade Level</label>
								</div>
							</div>
						</div>

						<div class="row mb-3">
							<div class="col-md-12">
								<div class="form-floating">
									<select class="form-control" id="newScholarship" name="newScholarship">
										<option value="yes">Yes</option>
										<option value="no">No</option>
									</select>
									<label for="newScholarship">Are you the recipient of a work-based
										scholarship?</label>
								</div>
							</div>
						</div>

						<button class="btn btn-primary w-100 py-2" type="submit">Submit</button>
					</form>

					<!-- END FORM INFO -->

				</div>
			</div>
		</div>
	</div>
@endsection