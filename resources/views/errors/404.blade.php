@extends('errors.layout', ['code' => 404])
@section('title', 'Page not found')
@section('icon', '🔎')
@section('heading', 'We can\'t find that page')
@section('message', 'The page may have moved, or the link may have a typo.')
@section('hint', 'Use the button below to go back to the home page and try again.')

