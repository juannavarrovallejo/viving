<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>doctor Consultation Portal</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">doctor Consultation Portal</div>

                    <div class="card-body">
                        <h3>doctor Approved Successfully</h3>

                        <h5 class="card-title">doctor ID: <strong>{{ $doctor->id }}</strong></h5>

                        <p class="card-text">doctor Name:
                            <strong>{{ $doctor->first_name ." ". $doctor->last_name }}</strong>
                        </p>
                        <p class="card-text"> doctor Experience: <strong>{{ $doctor->experience }}</strong></p>

                        <p class="card-text">doctor Address: <strong>{{ $doctor->address_line_1 }}</strong></p>

                        <p class="card-text">doctor Phone: <strong>{{ $doctor->cell_phone }}</strong></p>
                        <a href="{{ url('/doctors/profiles/' . $doctor->id) }}"
                            class="btn btn-primary text-white">Visit Your Profile</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

</body>

</html>
