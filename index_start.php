<div class="row d-flex justify-content-center align-items-center vh-100 m-0">
  <div class="col-12" style="max-width: 380px;">
    <div class="card shadow-sm auth-card">
      <div class="card-body p-4">
        <div class="row">
          <div class="col-12 text-center">
            <img class="img-fluid auth-logo" src="./img/logo.png" alt="">
          </div>
        </div>
        <div>
          <hr>
        </div>
        <div class="row">
          <div class="col-12 mb-2">
            <form id="formLogin">
              <label for="inpUsrLogin" class="form-label mb-0">Логин</label>
              <input type="text" class="form-control form-inp" id="inpUsrLogin" data-name="usr_login" data-type="text" data-required="1" value="" autocomplete="username">
              <label for="inpUsrPsw" class="form-label mt-2 mb-0">Пароль</label>
              <input type="password" class="form-control form-inp" id="inpUsrPsw" data-name="usr_pass" data-type="text" data-required="1" value="" autocomplete="current-password">
              <div class="row mt-3 g-2 justify-content-center">
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="7">7</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="8">8</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="9">9</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="4">4</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="5">5</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="6">6</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="1">1</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="2">2</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="3">3</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="submit" class="btn btnInNmb btn-dark" id="btnLogin">
                    <span id="btnText">Вход</span>
                    <div class="spinner-border spinner-border-sm d-none" id="divLoadingLoginForm"></div>
                  </button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="0">0</button>
                </div>
                <div class="col-4 d-grid">
                  <button type="button" class="btn btnInNmb" value="-1"><</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
