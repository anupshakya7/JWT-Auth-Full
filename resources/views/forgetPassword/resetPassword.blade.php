<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    @if($errors->any())
    <ul>
        @foreach($errors->all() as $error)
        <li>{{$error}}</li>
        @endforeach
    </ul>
    @endif
    <form method="POST">
        @csrf
        <input type="hidden" name="id" value="{{$user['id']}}">
        <input type="password" name="password" placeholder="New Password">
        <br><br>
        <input type="password" name="password_confirmation" placeholder="Confirm Password">
        <br><br>
        <input type="submit">
    </form>
</body>

</html>