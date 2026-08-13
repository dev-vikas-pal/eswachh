<app-layout-main-footer>
  <footer class="footer bg-white d-flex align-items-center pt-2 pb-2">
    <div class="col">
      <div class="row">
        <div class="col-lg-3">
          <div class="text-muted text-xs-center"> © 2021. Powered by <a href="https://www.eswachh.in" target="_blank">eSwachh </a>
          </div>
        </div>
        <div class="col text-center">
          <div>
            <a href="https://eswachh.in/order/termcondition" class="mr-2">Terms &amp; Conditions</a>
            <a href="https://eswachh.in/order/privacypolicy" class="mr-2">Privacy Policy</a>
          </div>
        </div>
        <div class="col-md-3 d-flex justify-content-md-end text-xs-center">
          <span class="m-sm-auto m-md-0 text-muted align-items-center"> Version 6.0.0.1 <img src="" title="" alt="" class="mr-md-2 footer-logo" style="width: 60px;">
          </span>
        </div>
      </div>
    </div>
  </footer>
</app-layout-main-footer>
<div class="control-sidebar-bg"></div>
</div>
<!---->
<!---->
</app-root>
<!-- JS-->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.min.js" type="text/javascript"></script>
<script src="assets/js/dataTables.bootstrap4.min.js"></script>
<script src="assets/js/checkout.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<div class="razorpay-container" style="z-index: 1000000000; position: fixed; top: 0px; display: none; left: 0px; height: 100%; width: 100%; backface-visibility: hidden; overflow-y: visible;">
  <style>
    @keyframes rzp-rot {
      to {
        transform: rotate(360deg);
      }
    }

    @-webkit-keyframes rzp-rot {
      to {
        -webkit-transform: rotate(360deg);
      }
    }
  </style>
  <div class="razorpay-backdrop" style="min-height: 100%; transition: all 0.3s ease-out 0s; position: fixed; top: 0px; left: 0px; width: 100%; height: 100%;">
    <span style="text-decoration: none; background: rgb(214, 68, 68); border: 1px dashed white; padding: 3px; opacity: 0; transform: rotate(45deg); transition: opacity 0.3s ease-in 0s; font-family: lato, ubuntu, helvetica, sans-serif; color: white; position: absolute; width: 200px; text-align: center; right: -50px; top: 50px;">Test Mode</span>
  </div>
  <iframe style="opacity: 1; height: 100%; position: relative; background: none; display: block; border: 0 none transparent; margin: 0px; padding: 0px; z-index: 2;" allowtransparency="true" frameborder="0" width="100%" height="100%" allowpaymentrequest="true" src="https://api.razorpay.com/v1/checkout/public" class="razorpay-checkout-frame"></iframe>
</div>
<link rel="stylesheet" href="https://cdn.datatables.net/1.10.16/css/jquery.dataTables.min.css">
<script src="assets/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdn.datatables.net/1.10.16/js/jquery.dataTables.min.js" type="text/javascript"></script>
<!-- jQuery -->
<!-- Bootstrap -->
<script src="assets/js/popper.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<!-- Plugins -->
<script src="assets/js/owl-carousel.js"></script>
<!-- Global Init -->