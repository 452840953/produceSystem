<?php
namespace app\index\controller;
use think\Session;
class Admin extends \think\Controller
{
	//登录
    public function login()
    {
        $data = db("admin")->where($_REQUEST)->select();
        if(count($data)==0){
            $j['status']=0;
            $j['meg']="登录失败";
            Session::set('id',$data[0]["id"]);
            Session::set('name',$data[0]["name"]);
        }else{
            $j['status']=1;
            $j['meg']="登录成功";
        }
        echo json_encode($j);
    }
    //获得当前用户
    public function get()
    {
        if(Session::has("id")){
            $j['status']=0;
            $j['meg']="用户未登录";
        }else{
            $data = db("admin")->where(Session::get("id"))->find();
            if(count($data)==0){
                $j['status']=0;
                $j['meg']="用户登录信息错误";
            }else{
                $j['status']=1;
                $j['meg']=$data;
            }
        }
        echo json_encode($j);
    }
    //注销
    public function logout()
    {
        Session::delete("id");
        $j['status']=0;
        $j['meg']="用户未登录";
        echo json_encode($j);
    }
}
