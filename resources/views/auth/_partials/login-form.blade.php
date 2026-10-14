<form class="theme-form" method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <h4>Sign in to account</h4>
    <p>Enter your email &amp; password to login</p>

    <div class="form-group">
        <label class="col-form-label">Email Address</label>
        <input id="email" class="form-control @error('email') is-invalid @enderror" type="email" name="email"
            value="{{ old('email') }}" required autocomplete="username" autofocus placeholder="admin@gmail.com">
        @error('email')
            <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
        @enderror
    </div>

    <div class="form-group">
        <label class="col-form-label">Password</label>
        <div class="form-input position-relative">
            <input id="password" class="form-control @error('password') is-invalid @enderror" type="password"
                name="password" required autocomplete="current-password" placeholder="*********">
            <div class="show-hide"><span class="show"> </span></div>
            @error('password')
                <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>

    <div class="form-group mb-0">
        <div class="form-check">
            <input class="form-check-input" id="remember" type="checkbox" name="remember" value="1"
                {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
        <div class="text-end">
            <button class="btn btn-primary btn-block w-100 mt-3 spinner-btn" type="submit">Sign in</button>
        </div>
    </div>
</form>
