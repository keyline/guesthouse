    <!-- top bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar__inner">
                <p class="top-bar__text">Discover Your Dream Guest House to Better Experience</p>
                <a href="tel:+919830098300" class="top-bar__phone">
                    <i class="fa-solid fa-phone"></i>
                    +91 98300 983XX
                </a>
            </div>
        </div>
    </div>

    <!-- header -->
    <header class="site-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
            <div class="site-header__inner">

                @php($publicIdentity = \Illuminate\Support\Facades\Schema::hasTable('settings') ? \App\Models\Setting::query()->first() : null)
                <a href="{{ url('/') }}" class="site-logo">
                    <img src="{{ $publicIdentity?->logo_path ? asset('storage/'.$publicIdentity->logo_path) : asset('landing/images/logo.svg') }}" alt="{{ $publicIdentity?->site_name ?: 'EENNRA Group of Guest Houses & Banquets' }}">
                </a>

                <nav class="main-nav">
                    <ul>
                        <li><a href="#">Home</a></li>
                        <li class="menu-item-has-children">
                            <a href="#">Service <i class="fa-solid fa-chevron-down"></i></a>
                            <ul class="sub-menu">
                                <li><a href="#">Room Booking</a></li>
                                <li><a href="#">Banquet Halls</a></li>
                                <li><a href="#">Catering</a></li>
                            </ul>
                        </li>
                        <li class="menu-item-has-children">
                            <a href="#">Facilities <i class="fa-solid fa-chevron-down"></i></a>
                            <ul class="sub-menu">
                                <li><a href="#">Parking</a></li>
                                <li><a href="#">Wi-Fi</a></li>
                                <li><a href="#">Swimming Pool</a></li>
                            </ul>
                        </li>
                    </ul>
                </nav>

                <div class="site-header__actions">
                    @php($authUser = auth()->user())
                    @if($authUser?->hasRole(\App\Models\User::ROLE_CUSTOMER))
                        @php($displayName = $authUser->name ?: 'My Account')
                        @php($firstName = \Illuminate\Support\Str::of($displayName)->trim()->explode(' ')->first() ?: 'Guest')
                        @php($initials = collect(preg_split('/\s+/', trim($displayName), -1, PREG_SPLIT_NO_EMPTY))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''))
                        @php($hour = (int) now()->format('G'))
                        @php($greetings = $hour < 12 ? ['Good morning ☀️', 'Rise & shine 🌤️', 'Welcome back 👋'] : ($hour < 17 ? ['Good afternoon 👋', 'Welcome back ✨', 'Good to see you 😊'] : ['Good evening 🌙', 'Welcome back ✨', 'Namaste 🙏']))
                        @php($greetLine = $greetings[array_rand($greetings)])
                        <div class="account-menu" data-account-menu>
                            <button type="button" class="account-menu__trigger" data-account-toggle aria-haspopup="true" aria-expanded="false">
                                <span class="account-menu__avatar">{{ $initials ?: 'U' }}</span>
                                <span class="account-menu__text">
                                    <span class="account-menu__greeting">{{ $greetLine }}</span>
                                    <span class="account-menu__name">{{ $firstName }}</span>
                                </span>
                                <i class="fa-solid fa-chevron-down account-menu__caret"></i>
                            </button>
                            <div class="account-menu__dropdown" data-account-dropdown hidden>
                                <a href="{{ route('customer.dashboard') }}" class="account-menu__item">
                                    <i class="fa-solid fa-user"></i> Profile
                                </a>
                                <form method="POST" action="{{ route('customer.logout') }}">
                                    @csrf
                                    <button type="submit" class="account-menu__item account-menu__item--logout">
                                        <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                                    </button>
                                </form>
                            </div>
                        </div>
                    @else
                                                                <a href="{{ route('customer.login') }}" class="btn-book-now" data-register-open>Login / Register</a>
                    @endif
                    <button type="button" class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Open menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>

            </div>
            </div>
            </div>
        </div>
    </header>

    @auth
    <script>
    (() => {
        const menu = document.querySelector('[data-account-menu]');
        if (!menu) return;
        const toggle = menu.querySelector('[data-account-toggle]');
        const dropdown = menu.querySelector('[data-account-dropdown]');
        const close = () => { dropdown.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
        toggle.addEventListener('click', (e) => {
            e.stopPropagation();
            const open = dropdown.hidden;
            dropdown.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', (e) => { if (!menu.contains(e.target)) close(); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape') close(); });
    })();
    </script>
    @endauth

    <!-- mobile offcanvas menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu__top">
            <a href="{{ url('/') }}" class="site-logo">
                <img src="{{ $publicIdentity?->logo_path ? asset('storage/'.$publicIdentity->logo_path) : asset('landing/images/logo.svg') }}" alt="{{ $publicIdentity?->site_name ?: 'EENNRA Group of Guest Houses & Banquets' }}">
            </a>
            <button type="button" class="mobile-menu__close" id="mobileMenuClose" aria-label="Close menu">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="mobile-menu__body">

            <nav class="mobile-menu__nav">
                <ul>
                    <li><a href="#">Home</a></li>
                    <li class="menu-item-has-children">
                        <div class="mobile-menu__row">
                            <a href="#">Service</a>
                            <button type="button" class="mobile-menu__toggle" aria-label="Toggle submenu">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <ul class="sub-menu">
                            <li><a href="#">Room Booking</a></li>
                            <li><a href="#">Banquet Halls</a></li>
                            <li><a href="#">Catering</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children">
                        <div class="mobile-menu__row">
                            <a href="#">Facilities</a>
                            <button type="button" class="mobile-menu__toggle" aria-label="Toggle submenu">
                                <i class="fa-solid fa-plus"></i>
                            </button>
                        </div>
                        <ul class="sub-menu">
                            <li><a href="#">Parking</a></li>
                            <li><a href="#">Wi-Fi</a></li>
                            <li><a href="#">Swimming Pool</a></li>
                        </ul>
                    </li>
                </ul>
            </nav>

            @if(auth()->user()?->hasRole(\App\Models\User::ROLE_CUSTOMER))
                <a href="{{ route('customer.dashboard') }}" class="btn-book-now mobile-menu__book">Profile</a>
                <form method="POST" action="{{ route('customer.logout') }}" class="mobile-menu__logout">@csrf<button type="submit" class="mobile-menu__logout-btn"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</button></form>
            @else<a href="{{ route('customer.login') }}" class="btn-book-now mobile-menu__book" data-register-open>Login / Register</a>@endif

            <div class="mobile-menu__footer">
                <a href="tel:+919830098300" class="mobile-menu__phone">
                    <i class="fa-solid fa-phone"></i>
                    +91 98300 983XX
                </a>
            </div>

        </div>
    </div>

    <div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

    @guest
    <div class="register-modal" data-register-modal hidden>
        <div class="register-modal__backdrop" data-register-close></div>
        <section class="register-modal__panel" role="dialog" aria-modal="true" aria-labelledby="registerTitle">
            <button type="button" class="register-modal__close" data-register-close aria-label="Close">×</button>
            <div class="register-modal__head"><p>Guest account</p><h2 id="registerTitle">Login or register in seconds</h2><span>Just enter your mobile number — we’ll log you in if you have an account, or set one up if you’re new.</span></div>

            <div class="register-modal__body">
                <div class="register-step active" data-register-step="phone">
                    <div class="register-account-tabs"><button type="button" class="active" data-account-type="individual">Personal account</button><button type="button" data-account-type="corporate">Corporate account</button></div>
                    <label class="register-label">Mobile number</label>
                    <div class="register-phone"><select data-register-code>@foreach(\App\Support\PhoneNumber::countryCodes() as $code=>$caption)<option value="{{ $code }}" @selected($code === '+91')>{{ $caption }}</option>@endforeach</select><input data-register-mobile inputmode="tel" autocomplete="tel" placeholder="Enter mobile number"></div>
                    <button type="button" class="register-primary" data-register-send>Continue</button>
                    <p class="register-error" data-register-error hidden></p>
                    <p class="register-legal">By continuing, you agree to the property’s privacy policy and guest terms.</p>
                </div>

                <div class="register-step" data-register-step="otp">
                    <button type="button" class="register-back" data-register-back>← Change number</button><h3>Verify mobile number</h3><p class="register-help">Enter the 6-digit OTP sent to <b data-register-phone-label></b>.</p>
                    <input class="register-otp" data-register-otp inputmode="numeric" maxlength="6" placeholder="••••••">
                    <p class="register-dev" data-register-dev hidden></p><button type="button" class="register-primary" data-register-verify>Verify OTP</button>
                    <p class="register-error" data-register-otp-error hidden></p>
                </div>

                <form class="register-step" data-register-step="profile" data-register-profile>
                    <div class="register-profile-title"><div><h3 data-profile-heading>Personal details</h3><p>Fields marked * are required.</p></div><span data-verified-phone></span></div>
                    <input type="hidden" name="customer_type" value="individual" data-customer-type>
                    <div class="register-grid">
                        <label class="register-field full"><span>Full legal name *</span><input name="name" autocomplete="name" required></label>
                        <label class="register-field"><span>Email</span><input name="email" type="email" autocomplete="email"></label>
                        <label class="register-field personal"><span>Date of birth</span><input name="date_of_birth" type="date"></label>
                        <label class="register-field personal"><span>Gender</span><select name="gender"><option value="not_specified">Prefer not to say</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></label>
                        <label class="register-field personal"><span>Nationality</span><input name="nationality" value="Indian"></label>
                        <label class="register-field corporate" hidden><span>Legal company name *</span><input name="legal_name"></label>
                        <label class="register-field corporate" hidden><span>Trade name</span><input name="trade_name"></label>
                        <label class="register-field corporate" hidden><span>GSTIN *</span><input name="gstin" maxlength="15" style="text-transform:uppercase"></label>
                        <label class="register-field corporate" hidden><span>PAN</span><input name="pan" maxlength="10" style="text-transform:uppercase"></label>
                        <label class="register-field full personal"><span>Residential address *</span><input name="address_line_1"></label>
                        <label class="register-field full corporate" hidden><span>Registered office address *</span><input name="office_address_line_1"></label>
                        <label class="register-field"><span>City *</span><input name="city" data-personal-location></label>
                        <label class="register-field corporate" hidden><span>Office city *</span><input name="office_city"></label>
                        <label class="register-field"><span>State *</span><input name="state" data-personal-location></label>
                        <label class="register-field corporate" hidden><span>Office state *</span><input name="office_state"></label>
                        <label class="register-field"><span>PIN / postal code *</span><input name="postal_code" data-personal-location></label>
                        <label class="register-field corporate" hidden><span>Office PIN *</span><input name="office_postal_code"></label>
                        <label class="register-field"><span>Country *</span><select name="country"><option value="India">India</option><option value="Bangladesh">Bangladesh</option><option value="Nepal">Nepal</option><option value="Bhutan">Bhutan</option><option value="United Kingdom">United Kingdom</option><option value="United States">United States</option><option value="Other">Other</option></select></label>
                    </div>
                    <button type="submit" class="register-primary">Create account</button><p class="register-error" data-register-profile-error hidden></p>
                </form>
            </div>
        </section>
    </div>
    @endguest

    @guest
    <script>
    (()=>{const modal=document.querySelector('[data-register-modal]');if(!modal)return;let type='individual';const steps=[...modal.querySelectorAll('[data-register-step]')];const show=s=>steps.forEach(x=>x.classList.toggle('active',x.dataset.registerStep===s));const error=(el,msg)=>{el.textContent=msg;el.hidden=false};const post=async(url,body)=>{try{const r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token())},credentials:'same-origin',body:JSON.stringify(body)});const data=await r.json().catch(()=>({message:r.status===419?'Your session expired. Refresh the page and try again.':'The server returned an invalid response.'}));return {ok:r.ok,data}}catch(e){return {ok:false,data:{message:'Unable to connect. Check your connection and try again.'}}}};
    document.querySelectorAll('[data-register-open]').forEach(b=>b.addEventListener('click',ev=>{ev.preventDefault();modal.hidden=false;document.body.classList.add('register-modal-open');show('phone');setTimeout(()=>modal.querySelector('[data-register-mobile]')?.focus(),50)}));modal.querySelectorAll('[data-register-close]').forEach(b=>b.addEventListener('click',()=>{modal.hidden=true;document.body.classList.remove('register-modal-open')}));
    modal.querySelectorAll('[data-account-type]').forEach(b=>b.addEventListener('click',()=>{type=b.dataset.accountType;modal.querySelectorAll('[data-account-type]').forEach(x=>x.classList.toggle('active',x===b))}));
    modal.querySelector('[data-register-send]').addEventListener('click',async()=>{const e=modal.querySelector('[data-register-error]');e.hidden=true;const b=modal.querySelector('[data-register-send]');b.disabled=true;b.textContent='Sending…';const {ok,data}=await post(@json(route('otp.start')),{country_code:modal.querySelector('[data-register-code]').value,mobile:modal.querySelector('[data-register-mobile]').value});b.disabled=false;b.textContent='Continue';if(!ok)return error(e,Object.values(data.errors||{}).flat()[0]||data.message||'Could not send OTP.');modal.querySelector('[data-register-phone-label]').textContent=data.phone_e164;const dev=modal.querySelector('[data-register-dev]');const otpInput=modal.querySelector('[data-register-otp]');dev.hidden=!data.dev_otp;if(data.dev_otp){dev.textContent=`Development OTP: ${data.dev_otp} · auto-filled · valid for 5 minutes`;otpInput.value=data.dev_otp}else{otpInput.value=''}show('otp');otpInput?.focus()});
    modal.querySelector('[data-register-back]').addEventListener('click',()=>show('phone'));modal.querySelector('[data-register-verify]').addEventListener('click',async()=>{const e=modal.querySelector('[data-register-otp-error]');e.hidden=true;const {ok,data}=await post(@json(route('otp.verify')),{country_code:modal.querySelector('[data-register-code]').value,mobile:modal.querySelector('[data-register-mobile]').value,otp:modal.querySelector('[data-register-otp]').value,registration_flow:true});if(!ok)return error(e,data.message||'OTP verification failed.');if(!data.requires_profile){window.location=@json(route('customer.dashboard'));return}const corporate=type==='corporate';modal.querySelector('[data-customer-type]').value=type;modal.querySelector('[data-profile-heading]').textContent=corporate?'Corporate account details':'Personal details';modal.querySelector('[data-verified-phone]').textContent='✓ '+data.phone;modal.querySelectorAll('.personal').forEach(x=>x.hidden=corporate);modal.querySelectorAll('.corporate').forEach(x=>x.hidden=!corporate);modal.querySelectorAll('[data-personal-location]').forEach(x=>x.closest('label').hidden=corporate);show('profile')});
    modal.querySelector('[data-register-profile]').addEventListener('submit',async ev=>{ev.preventDefault();const e=modal.querySelector('[data-register-profile-error]');e.hidden=true;const body=Object.fromEntries(new FormData(ev.target).entries());const b=ev.target.querySelector('[type=submit]');b.disabled=true;b.textContent='Creating account…';const {ok,data}=await post(@json(route('otp.register.complete')),body);b.disabled=false;b.textContent='Create account';if(!ok)return error(e,Object.values(data.errors||{}).flat()[0]||data.message||'Could not create account.');window.location.assign(data.redirect||@json(route('customer.dashboard')))});document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!modal.hidden){modal.hidden=true;document.body.classList.remove('register-modal-open')}})})();
    </script>
    @endguest
