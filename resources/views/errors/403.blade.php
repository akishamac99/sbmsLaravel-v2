@extends('errors.layout', ['code' => 403])
@section('title', 'Not allowed')
@section('icon', '🔒')
@section('heading', 'You can\'t open this page')
@section('message', 'Your account doesn\'t have permission to see this page. If you think this is a mistake, please ask the salon owner or manager.')
@section('hint', 'You may be signed in to a different account than you expected.')

