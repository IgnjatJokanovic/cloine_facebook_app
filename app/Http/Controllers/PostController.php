<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $validator = Validator::make(request()->all(), [
            'owner' => 'required',
            'creator' => 'required',
            'body' =>  'required_without_all:image,emotion,taged',
            'image' => 'required_without_all:body,emotion,taged',
            'emotion' => 'required_without_all:body,image,taged',
            'taged' => 'required_without_all:body,emotion,image',
        ]);


        if($validator->fails())
        {
            return response()->json(['error' => "Please fill out something on a post"], 422);
        }

        if(request()->image != null){

            $name = time()."-post.jpg";
            self::uploadImage($name, request()->image);
        }

        return response()->json("test");




    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private static function uploadImage(string $name, string $fileString)
    {
        if (str_contains($fileString, 'data:image')) {
            $image = base64_decode(substr($fileString, strpos($fileString, ",") + 1));
            \File::put(public_path() . "/img/$name", $image);
        }


    }
}
