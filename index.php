<?php
$host = "localhost";
$user = "root";
$password = "Zax6424900";
$dbname = "otdb";
$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) die("连接失败: " . $conn->connect_error);

// 获取所有分类
$categories = $conn->query("SELECT DISTINCT category FROM onlinetools");

// 当前分类
$current_category = isset($_GET['category']) ? $_GET['category'] : '';
?>
<!DOCTYPE html>
<html>
<head>
    <title>在线工具大全</title>
	<link rel="stylesheet" href="http://cdn.bugscaner.com/tools/css/bootstrap.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Microsoft YaHei', sans-serif; background: #f5f7fa; }
	   a:link, a:visited, a:hover, a:active {
           text-decoration: none;
        }
        /* 导航样式 */
        .navbar { background: #2c3e50; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; }
        .nav-list { display: flex; list-style: none; }
        .nav-item { margin-right: 20px; }
        .nav-link { color: #ecf0f1;  font-size: 18px; padding: 8px 15px; border-radius: 4px; }
        .nav-link:hover, .nav-link.active { background: #3498db; }
        
        /* 主内容区 */
        .container { max-width: 1200px; margin: 20px auto; padding: 0 15px; }
        .section-title { font-size: 24px; color: #2c3e50; margin: 30px 0 20px; padding-bottom: 10px; border-bottom: 2px solid #3498db; }
        
        /* 工具网格 */
        .tools-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
		
        .tool-card { background: white; border-radius: 8px;  box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s; display: flex;}
		.tool-card:hover { transform: translateY(-5px); }
		
		.tools-gridfl { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; }
		.tool-cardfl { background: white; border-radius: 8px;  box-shadow: 0 4px 8px rgba(0,0,0,0.1); transition: transform 0.3s;color: #eee;padding: 5px;text-align: center;}
		.tool-cardfl a{color: #eee;}
		.tool-cardfl img{ width: 80px; height: 80px;     border-radius: 10px;}
		.tool-cardfl:hover { transform: translateY(-5px);}
		.logofl {padding: 5px;}
        .tdesc {padding: 9px;color: #333;text-align: center;}
		

		
		.tool-icon {
            /*margin-right: 16px;*/
            flex-shrink: 0; /* 防止图片容器被压缩 */
			
        }
        
        .tool-icon img {
            width: 80px;
            height: 80px;
            object-fit: contain; /* 保持图片比例 */
            border-radius: 4px;
			margin: 10px;
        }
        
        .tool-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
		.tool-name {
			margin-bottom: 10px;
		}
        .tool-name a {
            font-size: 16px;
            font-weight: 700;
        }
        
        .tool-name a:hover {
            color: #007bff;
            
        }
		
		.tool-category {
            font-size: 12px;
            color: #fff;
			
			
			
        }
        
        .tool-category a {
            color: #fff;
			background-color: #5bc0de;
			padding: 0.2em 0.6em 0.3em;
			border-radius: 4px;
					
        }
		.tool-category a:hover {
            color: #fff;
           
        }
		
        
        /* 分类页样式 */
        .category-title { text-align: center; margin: 30px 0; color: #2c3e50; }
        .tool-grid-large { grid-template-columns: repeat(4, 1fr); }
        .tool-card-large { text-align: center; padding: 20px; }
        
        .tool-desc { color: #7f8c8d; font-size: 14px; margin-top: 10px; }
        
        /* 分页样式 */
        .pagination { display: flex; justify-content: center; margin: 30px 0; }
        .page-item { margin: 0 5px; }
        .page-link { display: block; padding: 8px 15px; background: #3498db; color: white; border-radius: 4px; }
        .page-link:hover { background: #2980b9; }
		
		.tools-footer {background-color: #2c3e50;color: #99979c;margin-top: 100px;padding-bottom: 50px;padding-top: 50px; text-align: center;box-sizing: border-box;}
        .tools-footer a {color: #fff;   margin-bottom: 20px;padding-left: 0;}
	    .tools-footer li {display: inline;}
    </style>
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="nav-container">
            <ul class="nav-list">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?= empty($current_category) ? 'active' : '' ?>">首页</a>
                </li>
                <?php while($cat = $categories->fetch_assoc()): ?>
                <li class="nav-item">
                    <a href="index.php?category=<?= urlencode($cat['category']) ?>" class="nav-link <?= $current_category === $cat['category'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat['category']) ?>
                    </a>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <?php if(empty($current_category)): ?>
            <!-- 首页显示所有分类 -->
            <?php
            $categories->data_seek(0);
            while($cat = $categories->fetch_assoc()):
                $tools = $conn->query("SELECT * FROM onlinetools WHERE category = '{$cat['category']}' ORDER BY `order` ASC LIMIT 20");
            ?>
            <h2 class="section-title"><?= htmlspecialchars($cat['category']) ?></h2>
            <div class="tools-grid">
                <?php while($tool = $tools->fetch_assoc()): ?>
                <div class="tool-card">
                   <div class="tool-icon">
				   <a href="<?= htmlspecialchars($tool['toolurl']) ?>" title="<?= htmlspecialchars($tool['onedesc']) ?>" data-toggle="popover" data-html="true"
        data-trigger="hover" data-placement="right" data-content=
        "<div style='width:200px;'><?= htmlspecialchars($tool['description']) ?></div>" target="_blank"><img src="<?= htmlspecialchars($tool['toollogo']) ?>" ></a>
				  </div>
				 <div class="tool-info">
                   <div class="tool-name">
			       <a href="<?= htmlspecialchars($tool['toolurl']) ?>"  target="_blank"><?= htmlspecialchars($tool['toolname']) ?></a>
				  </div>
                 <div class="tool-category">
				  <a href="?category=<?= htmlspecialchars($tool['category']) ?>"  target="_blank"><?= htmlspecialchars($tool['category']) ?></a>
				 </div>
			  </div>
			  
                </div>
                <?php endwhile; ?>
            </div>
            <?php endwhile; ?>
            
        <?php else: ?>
            <!-- 分类页显示 -->
            <h1 class="category-title"><?= htmlspecialchars($current_category) ?> </h1>
            <?php
            // 分页处理
            $perPage = 12;
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $offset = ($page - 1) * $perPage;
            
            $total = $conn->query("SELECT COUNT(*) FROM onlinetools WHERE category = '$current_category'")->fetch_row()[0];
            $totalPages = ceil($total / $perPage);
            
            $tools = $conn->query("SELECT * FROM onlinetools WHERE category = '$current_category' ORDER BY `order` ASC LIMIT $offset, $perPage");
            ?>
			
            <div class="tools-gridfl">
                <?php 
				$counter = 0; // 添加计数器用于标识每个元素
				while($tool = $tools->fetch_assoc()): 
                    $short_desc = mb_strlen($tool['description'], 'UTF-8') > 30 ? 
                                  mb_substr($tool['description'], 0, 30, 'UTF-8').'...' : 
                                  $tool['description'];
					
					$counter ++; // 每次循环计数器加1
                ?>             				
				
				<div  class="tool-cardfl">
				<div  class="logofl" id="random-color-<?= $counter ?>">
				<a href="<?= htmlspecialchars($tool['toolurl']) ?>"  target="_blank">
                    <img src="<?= htmlspecialchars($tool['toollogo']) ?>" ><br>
                    <em class="h3"><?= htmlspecialchars($tool['toolname']) ?></em></a>
				</div>
		
                    <div class="tdesc"><?= htmlspecialchars($short_desc) ?></div>
                </div>
				
    <!-- 在元素后面立即执行JS，确保元素已存在 -->
    <script>
        // 确保元素已加载到DOM中
        (function() {
            // 获取当前元素
            var element = document.getElementById('random-color-<?= $counter ?>');
            if (element) {
                // 生成随机暗色调
                function rdmRgbColor() {
                    let r = Math.floor(Math.random() * 128);
                    let g = Math.floor(Math.random() * 128);
                    let b = Math.floor(Math.random() * 128);
                    return `rgb(${r}, ${g}, ${b})`;
                }
                
                // 应用背景色
                element.style.backgroundColor = rdmRgbColor();
                // 可以添加一些额外样式确保显示正常
                element.style.padding = '10px';
                element.style.borderRadius = '4px';
            }
        })();
    </script>

                <?php endwhile; ?>
				
            </div>
            
            <!-- 分页控件 -->
            <?php if($totalPages > 1): ?>
            <div class="pagination">
                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                <div class="page-item">
                    <a href="index.php?category=<?= urlencode($current_category) ?>&page=<?= $i ?>" class="page-link <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                </div>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
	

        <div class="tools-footer">
            <ul>
                <li >
                    <a href="index.php" >&copy 2025</a>
                </li>
               <li >
                    <a href="index.php" >在线工具大全</a>
                </li>
            </ul>
        </div>

</body>
<script src="https://s1.pstatp.com/cdn/expire-1-M/jquery/1.11.1/jquery.min.js"></script>
<script src="http://cdn.bugscaner.com/tools/js/bootstrap.min.js"></script>
<script src="http://cdn.bugscaner.com/tools/js/toastr.min.js"></script>
<script type="text/javascript">$(function () { $("[data-toggle='tooltip']").tooltip(); });toastr.options.positionClass = 'toast-center-center';</script>
<script>
	$(function () { $("[data-toggle='popover']").popover(); });
</script><div style="display: none;"><script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?81c5c6d2c74d56c9ab654aec4c11e078";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>

</html>
<?php $conn->close(); ?>