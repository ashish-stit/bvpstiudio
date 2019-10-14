<?php
namespace App\Http\Controllers\API;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\User;
use App\User_FolderModel;
use App\MediaModal;
use Illuminate\Support\Facades\Auth;
use Validator;
use File;
use DB;
use SSH;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception;

class UploadFileController extends Controller
{
  protected $sshOutput = "";
  public function savefolder(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), [
        'user_id' => 'required',
        'folder_name' => 'required',
        'parent_id'=>'required',
        'action'=>'required',
        'folder_id'=>'required'
      ]);
      if ($validator->fails()) {
        $message = $validator->errors();
        return $this->respondWithError(400,$message,array());
      }
      switch ($request->action) {
        case "create":
          if ($request->parent_id == 0) {
            if (!file_exists('/var/www/html/public/user/user_'.$request->user_id)) {
              \SSH::into('S1')->run(array('sudo mkdir -m777 /var/www/html/public/user/user_'.$request->user_id));
            }
            if (!file_exists('/var/www/html/public/user/user_'.$request->user_id.'/'.$request->folder_name)) {
              \SSH::into('S1')->run(array('sudo mkdir -m777 /var/www/html/public/user/user_'.$request->user_id.'/'.$request->folder_name));
            } else {
              $message = "You have already created this folder";
              return $this->respondWithError(205,$message,array());
              }
            $user_folder = new User_FolderModel();
            $user_folder->user_id = $request->user_id;
            $user_folder->folder_name = $request->folder_name;
            $user_folder->parent_id = $request->parent_id;
            $user_folder->folder_status=0;
            $user_folder->save();
            $rows = DB::select('select * from user_project where user_id ='.$request->user_id);
            $folders = $this->folder_structure($this->objectToArray($rows));
            return $this->respondWithError(200,"Accepted",$folders);
          } else if ($request->parent_id > 0) {
            $rows = DB::select('select * from user_project where user_id='.$request->user_id." and id=".$request->parent_id);
            $parentFolder = $rows[0]->folder_name;
            \SSH::into('S1')->run(array('sudo find /var/www/html/public/user/user_'.$request->user_id.' -type d -name "'.$parentFolder.'"'),function($path) {
              $this->sshOutput = $path;
            });
            $newFolderPath = trim($this->sshOutput).'/'.$request->folder_name;
            //echo $newFolderPath;
            if (!file_exists($newFolderPath)) {
              \SSH::into('S1')->run(array('sudo mkdir -m 777 '.$newFolderPath));
            } else {
              $message = "You have already created this folder";
              return $this->respondWithError(205,$message,array());
            }
            $user_folder = new User_FolderModel();
            $user_folder->user_id = $request->user_id;
            $user_folder->folder_name = $request->folder_name;
            $user_folder->parent_id = $request->parent_id;
            $user_folder->folder_status=0;
            $user_folder->save();
            $rows = DB::select('select * from user_project where user_id ='.$request->user_id);
            $folders = $this->folder_structure($this->objectToArray($rows));
            return $this->respondWithError(200,"Accepted",$folders);
          }
          break;
        case "project":

          if ($request->folder_id == 0) {
            $rows = DB::select('select * from user_project where user_id='.$request->user_id." and id=".$request->parent_id);
            $parentFolder = $rows[0]->folder_name;
            \SSH::into('S1')->run(array('sudo find /var/www/html/public/user/user_'.$request->user_id.' -type d -name "'.$parentFolder.'"'),function($path) {

              $this->sshOutput = $path;
            });
            $newFolderPath = trim($this->sshOutput).'/'.$request->folder_name;
            //echo $newFolderPath;
            if (!file_exists($newFolderPath)) {
              \SSH::into('S1')->run(array('sudo mkdir -m 777 '.$newFolderPath));
            } else {
              $message = "You have already created this project";
              return $this->respondWithError(205,$message,array());
            }
            $user_folder = new User_FolderModel();
            $user_folder->user_id = $request->user_id;
            $user_folder->folder_name = $request->folder_name;
            $user_folder->parent_id = $request->parent_id;
            $user_folder->folder_status='1';
            $user_folder->save();
            $rows = DB::select('select * from user_project where user_id ='.$request->user_id);
            $folders = $this->folder_structure($this->objectToArray($rows));
            return $this->respondWithError(200,"Accepted",$folders);
          }
          
          break;
        case "update":
          $rows = DB::select('select * from user_project where user_id='.$request->user_id." and id=".$request->folder_id);
          $parentFolder = $rows[0]->folder_name;
          \SSH::into('S1')->run(array('sudo find /var/www/html/public/user/user_'.$request->user_id.' -type d -name "'.$parentFolder.'"'),function($path) {
            $this->sshOutput = $path;
          });
          $newPath = explode('/', trim($this->sshOutput));
          array_pop($newPath);
          $n = implode('/', $newPath);
          $p = $n.'/'.$request->folder_name;
          \SSH::into('S1')->run(array('sudo mv '.trim($this->sshOutput).' '.$p));
          User_FolderModel::where(array('user_id'=>$request->user_id,'id'=>$request->folder_id))->update(array('folder_name'=>$request->folder_name));
          $rows = DB::select('select * from user_project where user_id ='.$request->user_id);
          $folders = $this->folder_structure($this->objectToArray($rows));
          return $this->respondWithError(200,"Folder Updated",$folders);
          break;
        case "delete":
        try {
          $rows = DB::select('select * from user_project where user_id='.$request->user_id." and id=".$request->folder_id);
          $folderName = $rows[0]->folder_name;
          \SSH::into('S1')->run(array('sudo find /var/www/html/public/user/user_'.$request->user_id.' -type d -name "'.$folderName.'"'),function($path) {
            $this->sshOutput = $path;
          });
          $this->rrmdir(trim($this->sshOutput));
          $rows = DB::select('select * from user_project where user_id ='.$request->user_id);
          $folders = $this->folder_structure($this->objectToArray($rows));
          $data=User_FolderModel::find($request->folder_id);
          if ($data->delete()) {

            return $this->respondWithError(200,"Folder Deleted",$folders);
          } } catch (\Exception $e) {
            return $this->respondWithError(500,"Internal Server Error!",array());
          }
          break;
      }
    } catch (\Exception $e) {
      return $this->respondWithError(500,"Internal Server Error!",array());
    }
  }
  public function getfolder(Request $request)
  {
    $data=json_decode(file_get_contents('php://input'),true);
    $rows = DB::select('select * from user_project where user_id='.$request->user_id);

    $folders = $this->folder_structure($this->objectToArray($rows));
    $path = public_path().'/user/';
    $response = array('folders'=>array(),'path'=>'');
    $path = public_path().'/user/';
    $response['folders'] = $folders;
    $response['path'] = $path;
    return response()->json($response);
  }
  function objectToArray($d)
  {
    if (is_object($d)) {
      $d = get_object_vars($d);
    }
    if (is_array($d)) {
      return array_map(function ($value) {
        return (array)$value;
      }, $d);
    } else {
      return $d;
    }
  }
  function folder_structure(array $elements, $parentId = 0)
  {
    $returnarray = array();

    foreach ($elements as $element) {
      if ($element['parent_id'] == $parentId) {
        $children = $this->folder_structure($elements, $element['id']);
        if ($children) {
          $element['children'] = $children;
        }
        $returnarray[] = $element;
      }
    }
    return $returnarray;
  }
  public function uploadimg(Request $request)
  {
    try {
      $validator = Validator::make($request->all(), [
        'user_id' => 'required',
        'folder_id'=>'required',
        'media'=>'required'
      ]);
      if ($validator->fails()) {
        $message = $validator->errors();
        return $this->respondWithError(400,$message,array());
      }
      $file = $request->file('media');
      if (!$file->isValid()) {
        return $this->respondWithError(400,"Invalid Content",array());
      }
      $rows = DB::select('select * from user_project where user_id='.$request->user_id." and id=".$request->folder_id);
      $parentFolder = $rows[0]->folder_name;
      \SSH::into('S1')->run(array('sudo find /var/www/html/public/user/user_'.$request->user_id.' -type d -name "'.$parentFolder.'"'),function($path) {
        $this->sshOutput = $path;
      });
      $uploadPath = $this->sshOutput;
      $ext = explode('.',$file->getClientOriginalName());
      $extenstions = array(
        'image'=>array('jpg','jpeg','png'),
        'video'=>array('mp4','mov'),
        'txtfile'=>array('doc','docx','pdf')
      );  

      if (!in_array($ext[1], $extenstions['image']) && !in_array($ext[1], $extenstions['video']) && !in_array($ext[1], $extenstions['txtfile'])) {
        return $this->respondWithError(400,"Not an Image/Video/Text/Doc File",array());
      }
      $filename = uniqid('media_').'_'.date('Y-m-d-h-i-s');
      $filename = $filename.'.'.$ext[1];
      $data = $file->move(trim($uploadPath), $filename);
      $userimg = new MediaModal();
      $userimg->user_id = $request->user_id;
      $userimg->folder_id = $request->folder_id;
      $userimg->media_path = trim($data);
      $userimg->save();
      return $this->respondWithError(200,"File Uploaded Successfully!",array());
    } catch (\Exception $e) {
      return $this->respondWithError(500,"Internal Server Error!",array());
    }
  }

  public function uploadvideo(Request $request)
  {
  
    if ($file = $request->file('video')) {
      $path = public_path() . "/video";
      $priv = 0777;
      if (!file_exists($path)) {
        mkdir($path, $priv) ? true : false;
      }
      $name = uniqid($file->getClientOriginalName());
      $file->move($path, $name);
      return response()->json(compact('path'));
    }
  }
  public function uploadedfile(Request $res)
  {
    $file = $res->file('media');
    $ext = explode('.',$file->getClientOriginalName());
    $image = array( 'gif', 'jpg','jpeg','png');
    $video = array('mp4','video/avi');
    $txtfile = array('doc','docx','pptx','pps','pdf');

    if (in_array($ext[1], $image)) {
      if (!$res->hasFile('media')) {
        return response()->json(['file not found'], 400);
      }
      if (!$file->isValid()) {
        return response()->json(['invalid file'], 400);
      }
      $path = public_path() . '/img/';
      $file->move($path, $file->getClientOriginalName());
      return response()->json(compact('path'));
    } elseif (in_array($ext[1], $video)) {
      $path = public_path() . '/img';
      $priv = 0777;
      if (!file_exists($path)) {

        mkdir($path, $priv) ? true : false;
      }
      $name = $file->getClientOriginalName();
      $file->move($path, $name);
      return response()->json(compact('path'));

    } elseif (in_array($ext[1], $txtfile)) {
      $path = public_path() . "/img";
      $priv = 0777;
      if (!file_exists($path)) {
        mkdir($path, $priv) ? true : false;
      }
      $name = uniqid($file->getClientOriginalName());
      $file->move($path, $name);
      return response()->json(compact('path'));

    }
  }


  public function updatefolder(Request $request)
  {
    $user_id='14';
    $folder_name=$request->folder_name;
    $data=User_FolderModel::where('user_id',$user_id)->first();
    $oldfilename = public_path().'/user/'.$data->folder_name.'_'.$user_id;
    $path = public_path().'/user/'.$folder_name.'_'.$user_id;

    if (!file_exists($path)) {
      rename($oldfilename,$path) ? true : false;
    }
    $data->folder_name=$folder_name;
    if ($data->save()) {
      return response()->json(compact('data'));
    }
  }
  public function deletefolder()
  {
    $user_id='15';
    $data = User_FolderModel::where('user_id',$user_id)->first();
    $oldfilename = public_path().'/user/'.$data->folder_name.'_'.$user_id;
    //File::delete($oldfilename);
    unlink($oldfilename);
    if ($data->delete()) {
      return response()->json(compact('data'));
    }
  }
  private function respondWithError($code, $message, $data)
  {
    return response()->json(array('code'=>$code,'message'=>$message,'data'=>$data));
  }
  function rrmdir($dir)
  {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (is_dir($dir."/".$object))
            $this->rrmdir($dir."/".$object); else
            unlink($dir."/".$object);
        }
      }
      rmdir($dir);
    }
  }
  public function imagelist(Request $request)
  {
    $filename =$request->drive;
    print_r($filename);
    die;
    
    
  }
