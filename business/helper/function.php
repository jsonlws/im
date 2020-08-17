<?php

/**
 * 将json转为实体对象
 * @param  $class
 * @param  $jsonMap
 * @return bool
 */
function jsonForObject($class,$jsonMap)
{
    try {
        $class_obj = new ReflectionClass($class);//反射对象
        $class_instance = $class_obj->newInstance();//根据反射对象创建实例
        $methods = $class_obj->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method){
            if(preg_match("/^set(\w+)/",$method->getName(),$matches)){
                invokeSetterMethod($matches[1],$class_obj,$jsonMap,$class_instance);
            }
        }
        return $class_instance;
    }catch (Throwable $e){
        return false;
    }
}


function invokeSetterMethod($name,ReflectionClass $class_obj,$jsonMap,&$class_instance)
{
    # 相当于把MsgType 转化为 Msg_Type
    $filter_name = strtolower(preg_replace('/(?<=[a-z])([A-Z])/','_$1',$name));
    $pops = $class_obj->getProperties(ReflectionProperty::IS_PRIVATE);
    foreach ($pops as $pop){
        if(strtolower($pop->getName()) == $filter_name){
            #存在对应私有属性
            $method = $class_obj->getMethod('set'.$name);
            $args = $method->getParameters();
            if(count($args) == 1 && isset($jsonMap[$filter_name])){
                $method->invoke($class_instance,$jsonMap[$filter_name]);
            }
        }
    }
}
