<?php
namespace app\index\controller;
class Index extends \think\Controller
{
	//首页
    public function index()
    {
    	return $this->fetch('index');
    }
    //登录
    public function login()
    {
    	return $this->fetch('login');
    }
    //物料管理
    public function material()
    {
    	return $this->fetch('material');
    }
    //物料清单管理
    public function materiallist()
    {
    	return $this->fetch('materiallist');
    }
    //工艺路线管理
    public function processroute()
    {
    	return $this->fetch('processroute');
    }
    //班组管理
	public function team()
    {
    	return $this->fetch('team');
    }
    //生产计划管理——工作计划管理
    public function processplan()
    {
    	return $this->fetch('processplan');
    }
    //管理员
    public function admin()
    {
    	return $this->fetch('admin');
    }
}
