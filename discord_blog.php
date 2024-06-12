<?php
//connect to mysql server
//change format by your mysql servers info
//create mysql table (in my code using `discord` table)
//put the following code in mysql and execute plz
//CREATE TABLE `discord` (
// `created` timestamp NOT NULL DEFAULT current_timestamp(),
// `type` text DEFAULT NULL,
//  `id` int(11) NOT NULL,
//  `contents` text DEFAULT NULL,
//  `attachment` text DEFAULT NULL,
//  `attachment2` text DEFAULT NULL,
//  `attachment3` text DEFAULT NULL,
//  `msg_id` text DEFAULT NULL,
//  `reply_id` text DEFAULT NULL,
//  `created2` text DEFAULT '\'NONE\'',
//  `before_contents` text DEFAULT '\'NONE\'',
//  `name` text DEFAULT NULL
//) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

try {
    $db = new PDO ("mysql:dbname={$_ENV["DB_NAME"]};host={$_ENV["DB_HOST"]}; charset=utf8", $_ENV["DB_USER"], $_ENV["DB_PASS"]);
} catch (PDOException $e) {
    echo 'DB接続エラー' . $e->getMessage();
}
?>
<!DOCTYPE html>
 <html lang="ja">
  <head>
   <meta charset="UTF-8">
  <title>
  </title>
  <script src="https://cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js?lang=css&skin=sons-of-obsidian"></script>
  <link rel="stylesheet" href="/assets/css/sch_blog.css">
  <link rel="stylesheet" href="/assets/css/calendar.css">
  <link rel="stylesheet" href="/assets/css/pagenation.css">
  <link rel="stylesheet" href="/assets/css/blog.css">
 </head>
<body>
<br>
<?php
$pattern = '/((?:https?|ftp):\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/';

$sql = 'SHOW COLUMNS FROM discord;';
$stmt = $db->prepare($sql);
$stmt -> execute();
while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
$rows[]=$row;
}
$columns =[];
for($i=0;$i<=(count($rows)-1);$i++){
  if($rows[$i]["Field"]=="name"){
    continue;
  }
  if($rows[$i]["Field"]=="created"){
    continue;
  }
  if($rows[$i]["Field"]!=="created2"){
    $columns[] = $rows[$i]["Field"];
  }else{
    array_unshift($columns,$rows[$i]["Field"]);
  }
}

//created2を重複なくarrayに詰め込む
//以下、カレンダーのdataに入れる重複のない日付群
$sql="SELECT created2 from discord ORDER BY created DESC;";
$stmt = $db->prepare($sql);
$stmt -> execute();
$time_list=$stmt->fetchAll(PDO::FETCH_ASSOC);

$created2array = array_column($time_list,'created2');
$times = [];
for($i=0;$i<=(count($created2array)-1);$i++){
  if(!in_array($created2array[$i],$times)){
    $times[] = $created2array[$i];
  }
}
$times_ = array_flip($times);

$sql_days = '';
if(!empty($_GET)){
  if(isset($_GET['q'])){
    $pagenation = '';
    $sql = "SELECT * from discord ORDER BY created DESC;";
    $stmt=$db->prepare($sql);
    $stmt->execute();
    $results=$stmt->fetchAll(PDO::FETCH_ASSOC);  
  }
  elseif(isset($_GET['p'])){
    $pagenation = '';
    $p = intval($_GET['p']);
    $p_0 = $p-1;
    $p_1 = $p+1;
    $k = $n = 5;
    $q = $k*($p-1);

    if((count($times)-1)<$q){
      $last = false;
      $alart = "<a class='right font1-5 white under' href='?p=1'>1ページ目へ戻る</a><br><div class='red center'>ページ数が範囲外です</div>";
      die($alart);
    //日付の数を調整($timesの最後の5行目に差し掛かっていたら)
    }elseif((count($times)-1)<($q+$k)){
      $last = true;
      $k = ((count($times)-1)%$k)+1;
    }else{
      $last = false;
    }
    $days = array_slice($times,$q,$k);
    for($i=0;$i<=(count($days)-1);$i++){
      if($i==(count($days)-1)){
        $sql_days .= "'{$days[$i]}'";
      }else{
        $sql_days .= "'{$days[$i]}',";//カンマあり
      }
    }
    $sql = "SELECT * from discord WHERE created2 IN({$sql_days}) ORDER BY created DESC;";
    $stmt=$db->prepare($sql);
    $stmt->execute();
    $results=$stmt->fetchAll(PDO::FETCH_ASSOC);

    for($i=1;$i<=($p-1);$i++){
      if($p==0){
        break;
      }
      $pagenation .= "<a class='white' href='?p={$i}'>{$i}</a>";
    }
    $pagenation .= "<a class='white active' href='#'>{$p}</a>";
    //ページの最大数は$pに寄ってはいけない
    //ここは変えてはならない。基準になってる
    if($last){
      $m=intdiv(count($times)-1,$n)+1;
    }else{
      $m=intdiv(count($times)-1,$k)+1;
    }
    //echo "<div class='green'>ページの最大値(?):".$m."</div>";
    for($i=($p+1);$i<=$m;$i++){
      $pagenation .= "<a class='white' href='?p={$i}'>{$i}</a>";
    }
    //現在のページがページの最小値がであったら、左の矢印を非表示
    if($p==1){
      $pagenation = "<div class='pagination'><a class='v-hidden white' href='?p={$p_0}'>&laquo;</a>".$pagenation;
    }else{
      $pagenation = "<div class='pagination'><a class='white' href='?p={$p_0}'>&laquo;</a>".$pagenation;
    }    
    //現在のページがページの最大値がであったら、右の矢印を非表示に
    if($p==$m){
      $pagenation .= "<a class='v-hidden white' href='?p={$p_1}'>&raquo;</a></div>";
    }else{
      $pagenation .= "<a class='white' href='?p={$p_1}'>&raquo;</a></div>";
    }

    $showndays = '';
    for($i=0;$i<=(count($days)-1);$i++){
      $days[$i] = preg_replace("/_/","/",$days[$i]);
      $days[$i] = substr($days[$i],5);
      if($i==(count($days)-1)){
        $showndays .= $days[$i].' ';
      }else{
        $showndays .= $days[$i].', ';
      }
    }
    $pagenation = "<div class='font1-5'>{$showndays}を表示中</div>".$pagenation;
  }
}else{
  header("Location: " . $_SERVER['PHP_SELF']."?p=1");
  exit();
}
?>
  <script src="https://code.jquery.com/jquery.min.js"></script>
  <script>
  $(function() {
      $(".D2").click(function() {
          $(".E").slideToggle("");
      });
  });
  </script>
  <!--カレンダーを表示する-->
  <div class='c-flex'>
    <div class='center'>
      <?php echo $pagenation ?>
    </div>
    <div class="absolute_right">
      <div class="D2">
        <img style="width:30px;height:30px;margin-right:10px;" src='something-image-calendar'>
      </div>
    </div>
  </div>

    <div class="E">
      <div id="calendar"></div>
    </div>
<?php
//キー指定して、$result(array)から取り出す
$typearray = array_column($results,"type");

//typeを重複なくarrayに詰め込む
$typekind = [];
for($i=0;$i<=(count($typearray)-1);$i++){
  if(!in_array($typearray[$i],$typekind)){
    $typekind[] = $typearray[$i];
  }
}

//以下、for文($resultsを$iで回す)で使う変数を用意
for($k=0;$k<=(count($typekind)-1);$k++){
  ${$typekind[$k]} = '';
}

//昇順
//for($i=0;$i<=(count($results)-1);$i++){
//降順
//echo substr($results[0][$columns[1]],0,10).'<br><br>';
$comment = '';
for($i=0;$i<=(count($results)-1);$i++){
  //いろいろ定義-----------------------------------------------
  $one_side = '50';
  $icon_size = "style='width:".$one_side."px;height:".$one_side."px;'";
  // 返信idをもってたら
  if(isset($results[$i][$columns[8]])){
    $sql = 'SELECT * from discord WHERE msg_id=:msg_id;';
    $stmt=$db->prepare($sql);
    $stmt->bindValue(':msg_id', $results[$i][$columns[8]]);
    $stmt->execute();
    $og = $stmt->fetch(PDO::FETCH_ASSOC);
    if($og!==false){
      $separate = '';
      $img = ${$og['type']."_image"};
  
      if(isset($og['contents'])){
        $contents = substr($og['contents'],0,50);
        $contents .= '…';
      }else{
        $contents = '';
      }
      if(isset($og['attachment']) or isset($og['attachment2']) or isset($og['attachment3'])){
        $is_image = '何か画像が貼られています';
      }else{
        $is_image = '';
      }
  
      $f = intval($times_[$og["created2"]]);
      if($f==0){
        $f=1;
      }
      for($j=1;$j<=$m;$j++){
        if(((1+5*($j-1))<=$f+1)&&($f<=(5*$j))){
          $page = $j;
        }
      }
      $href = "~/index.php?p={$page}#{$results[$i][$columns[8]]}";
  
      $jump_button = "
        <div class='linkbox box17 white'>
          <div class='flex'>
            <div>
              <img src='something' width='32' height='32'>
            </div>
            <div class='trim_blog lang_icon'>
              <img src='{$img}'></img>
            </div>
          </div>
          <a href='{$href}'></a>
          <p>{$contents}</p>
          <p>{$is_image}</p>
        </div>
      ";
      }
  }else{
    $separate = '';
    $jump_button = '';
  }

  //下、$j<=(count($result[$i])-1)でも可
  for($j=0;$j<=(count($columns)-1);$j++){
    if($j==0){
      $showntime = preg_replace("/_/","/",$results[$i][$columns[0]]);
      if($i==0){
        //各時間に降られているidはカレンダーから飛ぶときに使う
        $y = "<div id='{$results[$i][$columns[0]]}' class='anchor3'></div><div class='center under'>{$showntime}</div><br>";
        echo $y;
      }elseif($results[$i][$columns[0]]!==$results[$i-1][$columns[0]]){
        $y = "<div id='{$results[$i][$columns[0]]}' class='anchor3'></div><p class='hr2'></p><div class='center under'>{$showntime}</div><br>";
        echo $y;
      }
    }
    if($j==3){
      $imgs = '';
      for($l=4;$l<=6;$l++){
        if($l==4){
          $imgs .= '<div class="c-flex">';
        }
        if(isset($results[$i][$columns[$l]])){
          $imgs .= "<div class='max_img center'><img src='{$results[$i][$columns[$l]]}' width='300' height='300'></div>";
        }
        if($l==6){
          $imgs .= '</div>';
        }
      }

      if($i==(count($results)-1)){
        for($k=0;$k<=(count($typekind)-1);$k++){
          $each_image = ${$typekind[$k]."_image"};
          $trim_image = "<div class='trim_blog lang_icon' ".$icon_size."><img src='{$each_image}'></img></div>";

          if($typekind[$k] == $results[$i][$columns[1]]){
            ${$typekind[$k]} = $trim_image."<br><div name='{$results[$i][$columns[7]]}' class='anchor3'></div><div style='padding-left:20px;padding-right:20px;'>".$results[$i][$columns[3]]."<br>".$imgs.${$typekind[$k]}."</div>";
          }
          echo ${$typekind[$k]};
          ${$typekind[$k]} = '';
        }
      }else{
        if(($results[$i][$columns[0]]!==$results[$i+1][$columns[0]])){
          for($k=0;$k<=(count($typekind)-1);$k++){
            $each_image = ${$typekind[$k]."_image"};
            $trim_image = "<div class='trim_blog lang_icon' ".$icon_size."><img src='{$each_image}'></img></div>";
  
            if($typekind[$k] == $results[$i][$columns[1]]){
              ${$typekind[$k]} = $jump_button."<div id='{$results[$i][$columns[7]]}' class='anchor3'></div>".$results[$i][$columns[3]].'<br>'.$imgs.${$typekind[$k]};
            }
            if(${$typekind[$k]}!==''){
              ${$typekind[$k]} = $trim_image.$jump_button."<br><div style='padding-left:20px;padding-right:20px;'>".${$typekind[$k]}.'</div>';
            }


            echo ${$typekind[$k]};
            ${$typekind[$k]} = '';
          }
        }else{
          for($k=0;$k<=(count($typekind)-1);$k++){
            if($typekind[$k] == $results[$i][$columns[1]]){
              ${$typekind[$k]} = $jump_button."<div id='{$results[$i][$columns[7]]}' class='anchor3'></div>".$results[$i][$columns[3]].'<br>'.$imgs.${$typekind[$k]};
            }
          }
        }
      } 
    }
  }
}
echo "<div class='center'>{$pagenation}</div>";
?>
</div>
  <?php include($space_under);?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  <script>
    $(document).ready(function() {
      $('a[href^="#"]').on('click', function(event) {
        event.preventDefault();
        var target = $(this.getAttribute('href'));
        if (target.length) {
          $('html, body').animate({
            scrollTop: target.offset().top
          }, 1000);
        }
      });
    });
  </script>

  <script>
      var data = [
      <?php
      for($i=0;$i<=(count($times_)-1);$i++){
        $f = intval($times_[$times[$i]]);
        if($f==0){
          $f=1;
        }
        for($j=1;$j<=$m;$j++){
          if(((1+5*($j-1))<=$f+1)&&($f<=(5*$j))){
            $page = $j;
          }
        }
        echo '{date: "'.$times[$i].'",link:"~/index.php?p='.$page.'#'.$times[$i].'"},';
      }

      ?>
  ];
  
  window.onload = function() {
      // 現在の年月の取得
      var current = new Date();
      var year = current.getFullYear();
      var month = current.getMonth() + 1;
   
      // カレンダーの表示
      var wrapper = document.getElementById('calendar');
      add_calendar(wrapper, year, month);
  }
   
  /**
   * 指定した年月のカレンダーを表示する
   * @param {object} wrapper - カレンダーを追加する親要素
   * @param {number} year    - 年の指定
   * @param {number} month   - 月の指定
   */
  function add_calendar(wrapper, year, month) {
      // 現在カレンダーが追加されている場合は一旦削除する
      wrapper.textContent = null;
   
      // カレンダーに表示する内容を取得
      var headData = generate_calendar_header(wrapper, year, month);
      var bodyData = generate_month_calendar(year, month, data);
   
      // カレンダーの要素を追加
      wrapper.appendChild(headData);
      wrapper.appendChild(bodyData);
  }
   
  /**
   * 指定した年月のカレンダーのヘッダー要素を生成して返す
   * @param {object} wrapper - カレンダーを追加する親要素
   * @param {number} year    - 年の指定
   * @param {number} month   - 月の指定
   */
  function generate_calendar_header(wrapper, year, month) {
      // 前月と翌月を取得
      var nextMonth = new Date(year, (month - 1));
      nextMonth.setMonth(nextMonth.getMonth() + 1);
      var prevMonth = new Date(year, (month - 1));
      prevMonth.setMonth(prevMonth.getMonth() - 1);
   
      // ヘッダー要素
      var cHeader = document.createElement('div');
      cHeader.className = 'calendar-header';
   
      // 見出しの追加
      var cTitle = document.createElement('div');
      cTitle.className = 'calendar-header__title';
      var cTitleText = document.createTextNode(year + '年' + month + '月');
      cTitle.appendChild(cTitleText);
      cHeader.appendChild(cTitle);
   
      // 前月ボタンの追加
      var cPrev = document.createElement('button');
      cPrev.className = 'calendar-header__prev';
      var cPrevText = document.createTextNode('前の月');
      cPrev.appendChild(cPrevText);
      // 前月ボタンをクリックした時のイベント設定
      cPrev.addEventListener('click', function() {
          add_calendar(wrapper, prevMonth.getFullYear(), (prevMonth.getMonth() + 1));
      }, false);
      cHeader.appendChild(cPrev);
   
      // 翌月ボタンの追加
      var cNext = document.createElement('button');
      cNext.className = 'calendar-header__next';
      var cNextText = document.createTextNode('次の月');
      cNext.appendChild(cNextText);
      // 翌月ボタンをクリックした時のイベント設定
      cNext.addEventListener('click', function() {
          add_calendar(wrapper, nextMonth.getFullYear(), (nextMonth.getMonth() + 1));
      }, false);
      cHeader.appendChild(cNext);
   
      return cHeader;
  }
   
  /**
   * 指定した年月のカレンダー要素を生成して返す
   * @param {number} year     - 年の指定
   * @param {number} month    - 月の指定
   * @param {object} linkData - リンクを設定する日付の情報
   */
  function generate_month_calendar(year, month, linkData) {
      var weekdayData = ['<div style="color: white">日</div>', '<div style="color: white">月</div>', '<div style="color: white">火</div>', '<div style="color: white">水</div>', '<div style="color: white">木</div>', '<div style="color: white">金</div>', '<div style="color: white">土</div>'];
      // カレンダーの情報を取得
      var calendarData = get_month_calendar(year, month);
   
      var i = calendarData[0]['weekday']; // 初日の曜日を取得
      // カレンダー上の初日より前を埋める
      while(i > 0) {
          i--;
          calendarData.unshift({
              day: '',
              weekday: i
          });
      }
      var i = calendarData[calendarData.length - 1]['weekday']; // 末日の曜日を取得
      // カレンダー上の末日より後を埋める
      while(i < 6) {
          i++;
          calendarData.push({
              day: '',
              weekday: i
          });
      }
   
      // カレンダーの要素を生成
      var cTable = document.createElement('table');
      cTable.className = 'calendar-table';
   
      // 自作
      // 今日の日付を取得できるnew Dateを格納
      const current_today = new Date().toLocaleDateString('ja-JP').replaceAll('/', '_')

      
      var insertData = '';
      // 曜日部分の生成
      insertData += '<thead>';
      insertData += '<tr>';
      for (var i = 0; i < weekdayData.length; i++) {
          insertData += '<th>';
          insertData += weekdayData[i];
          insertData += '</th>';
      }
      insertData += '</tr>';
      insertData += '</thead>';
   
      // 日付部分の生成
      insertData += '<tbody>';
      for (var i = 0; i < calendarData.length; i++) {
          if(calendarData[i]['weekday'] <= 0) {
              insertData += '<tr>';
          }
          insertData += '<td>';
              var ymd = year + '_' + month + '_' + calendarData[i]['day'];
              for (var j = 0; j < linkData.length; j++) {
                  if(linkData[j]['date'] === ymd) {
                    if(linkData[j]['date']===current_today){
                    insertData += '<a class="circle under" href="' + linkData[j]['link'] + '">' + calendarData[i]['day'] + '</a>';
                  }else{
                    insertData += '<a class="under" href="' + linkData[j]['link'] + '">' + calendarData[i]['day'] + '</a>';

                  }
                      break;
                  }

                  if(j >= linkData.length - 1) {
                      insertData += calendarData[i]['day'];
                  }
              }
          insertData += '</td>';
          if(calendarData[i]['weekday'] >= 6) {
              insertData += '</tr>';
          }
      }
      insertData += '</tbody>';
   
      cTable.innerHTML = insertData;
      return cTable;
  }
   
  /**
   * 指定した年月のカレンダー情報を返す
   * @param {number} year  - 年の指定
   * @param {number} month - 月の指定
   */
  function get_month_calendar(year, month) {
      var firstDate = new Date(year, (month - 1), 1); // 指定した年月の初日の情報
      var lastDay = new Date(year, (firstDate.getMonth() + 1), 0).getDate(); // 指定した年月の末日
      var weekday = firstDate.getDay(); // 指定した年月の初日の曜日
   
      var calendarData = []; // カレンダーの情報を格納
      var weekdayCount = weekday; // 曜日のカウント用
      for (var i = 0; i < lastDay; i++) {
          calendarData[i] = {
              day: i + 1,
              weekday: weekdayCount
          }
          // 曜日のカウントが6(土曜日)まできたら0(日曜日)に戻す
          if(weekdayCount >= 6) {
              weekdayCount = 0;
          } else {
              weekdayCount++;
          }
      }
      return calendarData;
  }
  </script>
    </body>
</html>
