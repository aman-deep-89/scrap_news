<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="assets/bootstrap.min.css" rel="stylesheet">
    <title>Scraping</title>
  </head>
  <body>
    <h1>Enter fields you want to scrap from</h1>
    <div class="container">
    <?php //echo time().'='.strtotime(date('Y-m-d H:00:00')); ?>
    <form class="row g-3" action="sample.php" method="post" id="get_data">
        <div class="mb-3">
            <label for="exampleFormControlInput1" class="form-label">Website URL</label>
            <input type="text" class="form-control" name="url" id="exampleFormControlInput1" required placeholder="Website Name" value='https://thefly.com/news.php'>
        </div>
        <div class="mb-3">
            <label for="exampleFormControlInpput21" class="form-label">Keywords you want to scrap</label>
            <input type="text" class="form-control" name="keywords" required id="exampleFormControlInpput21" value='call volume above normal and directionally bullish' />
        </div>
        <div class="mb-3">
            <label for="dt" class="form-label">Search Untill</label>
            <input type="date" class="form-control" name="date" required id="dt" value='<?= date('Y-m-d') ?>' />
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary mb-3">Scrap Data</button>
            <button type="button" class='btn btn-info' id="loader" style="display:none">Loading..</button>
            <div class="alert alert-success" id="msg" style="display:none"></div>
            <div class="alert alert-danger" id="error" style="display:none"></div>
        </div>
    </form>
    <div class="col-12">
        <div id="result" class="img-thumbnail"></div>
    </div>
</div>
    <script src="assets/jquery.min.js"></script>
    <script src="assets/bootstrap.bundle.min.js"></script>
    <script>
        $(function() {
            $('#get_data').submit(function(e) {
                e.preventDefault();        
            var $action=$('#get_data').attr("action");
            $('#loader').show();
            $('#result').html("");
            $.post($action,$('#get_data').serialize(),function(res) {
                $('#loader').hide();
              if(res.success) {
                  var resp='';
                  $('#msg').html(res.success_msg).slideDown().delay(5000).slideUp();;
                  $.each(res.message,function(index,item){
                    resp+='<h3>'+(index+1)+'</h3><p>'+item+'</p>';
                    console.log(item);
                  });
                  $('#result').html(resp);
              } else {
                 $('#error').html(res.error).slideDown().delay(5000).slideUp();
              } 
              $('#loader').hide();        
          },'json');
       });        
    });
    </script>
  </body>
</html>