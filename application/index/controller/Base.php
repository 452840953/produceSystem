<?php
namespace app\index\controller;
use think\Request;
class Base extends \think\Controller
{
    //二维码
    public function UserImg(){
        
    }

    public function allp($value='')
    {
        # code...
        $project = db("produceplan")->select();
        for ($i=0; $i < count($project); $i++) { 
            # code...
            $project[$i]["work"]=db("workplan2")->where("createtime",$project[$i]["id"])->select();
            $hour = 0;
            for ($j=0; $j < count($project[$i]["work"]); $j++) { 
                //时间花费
                $teamid = $project[$i]["work"][$j]['teamid'];
                $totalprice = $project[$i]["work"][$j]['price']*$project[$i]["work"][$j]['num'];
                //获得小队速率
                $team = db("team")->where("id",$teamid)->find();
                $moneyAhour = $team["moneyAhour"]; 
                $hour = $hour+round($totalprice/$moneyAhour,2);
                //判断完工并且库存入库
                $createtime = $project[$i]["work"][0]['updatetime'];
                $ct = strtotime($createtime);
                $et = $ct+($hour*60*60);
                if($et<time()){
                    //状态修改与否
                    if($project[$i]["work"][$j]["status"]=="生产中"){
                        //做修改
                        $project[$i]["work"][$j]["status"] = "已完工";
                        //下一个投入生产
                        if($j+1< count($project[$i]["work"])){
                            $nextp = $project[$i]["work"][$j+1];
                            $nextp["status"]=="生产中";
                            db("workplan2")->update($nextp);
                        }
                        db("workplan2")->update($project[$i]["work"][$j]);
                        //入库
                        $materialid = $project[$i]["work"][$j]["materialid"];
                        $m = db("material")->where("id",$materialid)->find();
                        $m["num"]=$m["num"]+$project[$i]["work"][$j]['num'];
                        db("material")->update($m);
                    }
                }else{
                    $project[$i]["work"][$j]["status"]="生产中";
                    db("workplan2")->update($project[$i]["work"][$j]);
                }
                //获得平均时间
                $project[$i]["work"][$j]["t"]=round($totalprice/$moneyAhour,2);
                # code...
                $project[$i]["work"][$j]["materiallist"]=db("materiallist")->where("workplanid",$project[$i]["work"][$j]["id"])->select();               
            }
            //判断当前项目状态
            $createtime = $project[$i]["work"][0]['updatetime'];
            $ct = strtotime($createtime);
            $et = $ct+($hour*60*60);
            if($et<time()){
                $project[$i]['status']="已完工";
            }else{
                $project[$i]['status']="生产中";
            }
            vendor('phpqrcode');//引入类库
            $value = 'http://localhost:90/banshi/public/nowstatus.html?id='.$project[$i]["id"];         //二维码内容
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            //生成二维码图片
            // 判断是否有这个文件夹  没有的话就创建一个
            if(!is_dir("qrcode")){
                // 创建文件加
                mkdir("qrcode");
            }
            //设置二维码文件名
            $filename = 'qrcode/'.time().rand(10000,9999999).'.png';
            //生成二维码
            \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
            $project[$i]["qrcode"]=$filename;
        }
        return $project;
    }
    public function addproject()
    {
        # code...
        //生产计划生成
        $proplan["name"]=$_REQUEST['name'];
        $proplan["descs"]=$_REQUEST['descs'];
        $proplan["status"]="投产中";
        db("produceplan")->insert($proplan);
        $pro = db("produceplan")->order("id desc")->find();
        //工作计划生成
        $work=json_decode($_REQUEST["workplan"],true);
        for ($i=0; $i < count($work); $i++) { 
            # code...
            $v=$work[$i];
            $w["materialid"]=$v[0];
            $w["routeid"]=$v[1];
            $w["routeid2"]=$v[2];
            $w["teamid"]=$v[3];
            $w["price"]=$v[4];
            $w["num"]=$v[5];
            $w["descs"]=$v[6];
            if($i==0){
                $w["status"]="生产中";
            }else{
                $w["status"]="待生产";
            }
            $w["createtime"]=$pro["id"];
            $w["updatetime"]=date("Y-m-d H:i:s");
            db("workplan2")->insert($w);
        }
        if($_REQUEST["materiallist"]==null){
            $list=json_decode($_REQUEST["materiallist"],true);
            for ($i=0; $i < count($list); $i++) { 
                # code...
                $material = db("material")->where("id",$v[0])->find();
                $v=$list[$i];
                $l["materialid"]=$v[0];
                $l["num"]=$v[1];
                $l["name"]=$material["name"];
                $l["descs"]=$v[2];
                $l["workplanid"]=$v[4];
                db("materiallist")->insert($l);
                $material["num"]=$material["num"]-$l["num"];
                db("material")->update($material);
            }
        }
        $j['status']="1";
        $j['meg']="计划已投产";
        return json_encode($j);
    }
    //当前项目进度
    public function getnowwork()
    {
        $id = $_REQUEST['id'];
        $data = ['createtime' => $id, 'status' => '生产中'];
        $workplan = db("workplan2")->where($data)->find();
        $showtime=date("Y-m-d H:i:s");
        if($workplan==null){
            $project = db("produceplan")->where("id",$id)->find();
            $project['status']="已完工";
            $project['time']=$showtime;
            return $project;
        }else{
            $workplan['time']=$showtime;
            $workplan['total']=$workplan['price']*$workplan['num'];
            $workplan['material']=(db("material")->where("id",$workplan['materialid'])->find())['name'];
            $workplan['route1']=(db("processroute")->where("id",$workplan['routeid'])->find())['name'];
            $workplan['route2']=(db("processroute")->where("id",$workplan['routeid2'])->find())['name'];
        }
        return $workplan;
    }
	//查找
    public function get()
    {
        $db = $_REQUEST["db"];
        unset($_REQUEST["db"]);
        $data = db($db)->where($_REQUEST)->select();
        if($db="team"){
            for ($i=0; $i < count($data); $i++) { 
                # code...
                $if["teamid"]=$data[$i]['id'];
                $if["status"]=1;
                if(count(db("workplan2")->where($if)->select())==0){
                    $data[$i]['status']="空闲中";
                }else{
                    $data[$i]['status']="生产中";
                }
            }
        }
        if(count($data)==0){
            $j['status']=0;
            $j['meg']="获取失败";
        }else{
            $j['status']=1;
            $j['meg']=$data;
        }
        echo json_encode($j);
    }
    //增肌
    public function add()
    {
        $db = $_REQUEST["db"];
        // dump($_REQUEST);
        unset($_REQUEST["db"]);
        if($db=="material"||$db=="processroute"||$db=="team"){
            $data = db($db)->where("name",$_REQUEST["name"])->select();
            if(count($data)==0){
                $data = db($db)->insert($_REQUEST);
            }else{
                $j['status']=0;
                $j['meg']="重名！";    
                echo json_encode($j);
                return;
            }
        }else{
            $data = db($db)->insert($_REQUEST);
        }
        if($data==0){
            $j['status']=0;
            $j['meg']="新增失败";
        }else{
            $j['status']=1;
            $j['meg']="新增成功";
        }
        echo json_encode($j);
    }
    //删除
    public function del()
    {
        $db = $_REQUEST["db"];
        unset($_REQUEST["db"]);
        $data = db($db)->where($_REQUEST)->delete();
        if($data==0){
            $j['status']=0;
            $j['meg']="删除失败";
        }else{
            $j['status']=1;
            $j['meg']="删除成功";
        }
        echo json_encode($j);
    }
    //修改
    public function change()
    {
        $db = $_REQUEST["db"];
        unset($_REQUEST["db"]);
        if($db=="material"){
            $data = db($db)->where("id",$_REQUEST["id"])->find();
            $data['num'] = $_REQUEST['num'];
            $data = db($db)->where("id",$_REQUEST["id"])->update($data);
        }else{
            $data = db($db)->where("id",$_REQUEST["id"])->update($_REQUEST);
        }
        if($data==0){
            $j['status']=0;
            $j['meg']="修改失败";
        }else{
            $j['status']=1;
            $j['meg']="修改成功";
        }
        echo json_encode($j);
    }
    //查询
    public function search()
    {
        $data = db($_REQUEST["db"])->where($_REQUEST["item"],'like','%'.$_REQUEST["value"].'%')->select();
        if($_REQUEST["db"]=="team"){
            for ($i=0; $i < count($data); $i++) { 
                # code...
                $if["teamid"]=$data[$i]['id'];
                $if["status"]=1;
                if(count(db("workplan2")->where($if)->select())==0){
                    $data[$i]['status']="空闲中";
                }else{
                    $data[$i]['status']="生产中";
                }
            }
        }else if($_REQUEST["db"]=="produceplan"){
            $project=$data;
            for ($i=0; $i < count($project); $i++) { 
                # code...
                $project[$i]["work"]=db("workplan2")->where("createtime",$project[$i]["id"])->select();
                $hour = 0;
                for ($j=0; $j < count($project[$i]["work"]); $j++) { 
                    //时间花费
                    $teamid = $project[$i]["work"][$j]['teamid'];
                    $totalprice = $project[$i]["work"][$j]['price']*$project[$i]["work"][$j]['num'];
                    //获得小队速率
                    $team = db("team")->where("id",$teamid)->find();
                    $moneyAhour = $team["moneyAhour"]; 
                    $hour = $hour+round($totalprice/$moneyAhour,2);
                    //判断完工并且库存入库
                    $createtime = $project[$i]["work"][0]['updatetime'];
                    $ct = strtotime($createtime);
                    $et = $ct+($hour*60*60);
                    if($et<time()){
                        //状态修改与否
                        if($project[$i]["work"][$j]["status"]=="生产中"){
                            //做修改
                            $project[$i]["work"][$j]["status"] = "已完工";
                            //下一个投入生产
                            if($j+1< count($project[$i]["work"])){
                                $nextp = $project[$i]["work"][$j+1];
                                $nextp["status"]=="生产中";
                                db("workplan2")->update($nextp);
                            }
                            db("workplan2")->update($project[$i]["work"][$j]);
                            //入库
                            $materialid = $project[$i]["work"][$j]["materialid"];
                            $m = db("material")->where("id",$materialid)->find();
                            $m["num"]=$m["num"]+$project[$i]["work"][$j]['num'];
                            db("material")->update($m);
                        }
                    }else{
                        $project[$i]["work"][$j]["status"]="生产中";
                        db("workplan2")->update($project[$i]["work"][$j]);
                    }
                    //获得平均时间
                    $project[$i]["work"][$j]["t"]=round($totalprice/$moneyAhour,2);
                    # code...
                    $project[$i]["work"][$j]["materiallist"]=db("materiallist")->where("workplanid",$project[$i]["work"][$j]["id"])->select();               
                }
                //判断当前项目状态
                $createtime = $project[$i]["work"][0]['updatetime'];
                $ct = strtotime($createtime);
                $et = $ct+($hour*60*60);
                if($et<time()){
                    $project[$i]['status']="已完工";
                }else{
                    $project[$i]['status']="生产中";
                }
                vendor('phpqrcode');//引入类库
                $value = 'http://localhost:90/banshi/public/nowstatus.html?id='.$project[$i]["id"];         //二维码内容
                $errorCorrectionLevel = 'L';  //容错级别
                $matrixPointSize = 5;      //生成图片大小
                //生成二维码图片
                // 判断是否有这个文件夹  没有的话就创建一个
                if(!is_dir("qrcode")){
                    // 创建文件加
                    mkdir("qrcode");
                }
                //设置二维码文件名
                $filename = 'qrcode/'.time().rand(10000,9999999).'.png';
                //生成二维码
                \QRcode::png($value,$filename , $errorCorrectionLevel, $matrixPointSize, 2);
                $project[$i]["qrcode"]=$filename;
            }
            return $project;
        }
        if($data==0){
            $j['status']=0;
            $j['meg']="删除失败";
        }else{
            $j['status']=1;
            $j['meg']=$data;
        }
        echo json_encode($j);
    }
}
