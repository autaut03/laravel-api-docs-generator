<!-- START_{{$parsedRoute['id']}} -->
@if($parsedRoute['title'] != '')## {{ $parsedRoute['title']}}
@else## {{$parsedRoute['uri']}}
@endif
@if($parsedRoute['description'])

    {!! $parsedRoute['description'] !!}
@endif

> Responses:
@forelse($parsedRoute['responses'] as $response)
```json
@if(is_object($response) || is_array($response))
{!! json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@elseif(is_string($response))
{!! $response !!}
@else
<!-- Even though it should never be called, I'll leave it there for now. -->
{!! json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) !!}
@endif
```
@empty
> Never returns anything
@endforelse

### HTTP Request
@foreach($parsedRoute['methods'] as $method)
`{{$method}} {{$parsedRoute['uri']}}`

@endforeach

@include('apidoc::partials.parameters', ['title' => 'Path parameters', 'parameters' => $parsedRoute['parameters']['path']])
@include('apidoc::partials.parameters', ['title' => 'Query/Post parameters', 'parameters' => $parsedRoute['parameters']['query']])

<!-- END_{{$parsedRoute['id']}} -->
