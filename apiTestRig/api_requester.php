<html>
<head>
<link rel="stylesheet" href="css/report.css" />
<script src="js/jquery-1.9.1.js"></script>
<script src="js/forge.min.js"></script>
<script src="js/ebysCrypto.js"></script>
<script type="text/javascript">

$('document').ready(
        function(){
            var secret = '';
            var key = '';

            $('#postsubmit').click(function(){
                var url = $('#txt_url').val();
                var postdata = $('#postdata').val();
                var method = $('#sel_method').val();
                var authHeader = '';
                var context = {};
                $.ebysCrypto.generateKeys()
                $authString = $('#hdn_auth').val();
                if ($authString.length > 0) {
                    context = JSON.parse($authString);
                    authHeader = $.ebysCrypto.buildApiAuthHeader(url, method, context, key, secret);
                }
                $.ajax( {
                        'url' : url,
                        'type' : method,
                        'data' : postdata,
                        'processData' : false,
                        'contentType' : 'application/json',
                        'beforeSend' : function (xhr){
                            xhr.setRequestHeader('Authorization', authHeader);
                            xhr.setRequestHeader('public-key', $.ebysCrypto.publicPem64);
                        },
                        'complete' : function(xhr, status){
                            try {
                                if (xhr.responseText.substr(0, 5) == 'while') {
                                	 $('#responsedata').html(xhr.responseText.substr(9));
                                } else {
                                    if (status !== 'error') {
                                        var fileKey = xhr.getResponseHeader('file-key');
                                        if (fileKey) {
                                        	$('#responsedata').html(escapeHtml($.ebysCrypto.decryptBookPage(fileKey, xhr.responseText)));
                                        } else {
                                            $('#responsedata').html(xhr.responseText);
                                        }
                                    } else {
                                        $('#responsedata').html('Not a valid json response :\n' + escapeHtml(xhr.responseText));
                                    }
                                }
                            } catch (e) {
                                $('#responsedata').html('Error in processing response:\n' + e.message + '\nResponse:\n' + escapeHtml(xhr.responseText));
                            }
                        }
                    });
            });
            $('#btn_login').click(function(){
                $('#loggedin').html('Logged out');
                var url = $('#txt_loginurl').val(); ;
                var postdata = {
                        "email" : $('#txt_email').val(),
                        "password" : $('#txt_pwd').val()
                };
                postdata = JSON.stringify(postdata);
                var authHeader = $.ebysCrypto.buildApiAuthHeader(url, "post", null, key, secret);
                $.ajax( {
                        'url' : url,
                        'type' : 'post',
                        'data' : postdata,
                        'processData' : false,
                        'contentType' : 'application/json',
                        'beforeSend' : function (xhr){
                                xhr.setRequestHeader('Authorization', authHeader);
                        },
                        'complete' : function(xhr, status){
                            jString = xhr.responseText.substr(9);
                            if ((obj = JSON.parse(jString)) && obj.token) {
                                $('#hdn_auth').val(jString);
                                $('#responsedata').html(jString);
                                $('#loggedin').html('Logged in');
                            } else {
                                $('#hdn_auth').val('');
                                $('#loggedin').html('Logged out');
                                $('#responsedata').html(jString);
                                $('#responsedata').html('Not an acceptable login response :\n' + xhr.responseText);
                            }
                        }
                    });
            });
        }
);

var entityMap = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': '&quot;',
    "'": '&#39;',
    "/": '&#x2F;'
};

function escapeHtml(string) {
    return String(string).replace(/[&<>"'\/]/g, function (s) {
        return entityMap[s];
        });
}

</script>
</head>

<body>
	<center>
		<div>
			<h1>Authenticating Api Request Generator</h1>
			<input type="text" id="txt_loginurl"
				value="http://api.localhost/v1/person/login" size="80"
				class="request-form"><br /> <input type="text" id="txt_email"
				value="andrew.boxer@anobii.com" class="request-form"> <input
				type="password" id="txt_pwd" value="griswald" class="request-form">
			<input type="button" id="btn_login" value="Log In"
				class="request-form"> <span id="loggedin">Logged out</span> <br /> <input
				type="hidden" id="hdn_auth" value=""><br /> <br /> <select
				id="sel_method">
				<option value="GET" selected="true">GET</option>
				<option value="POST">POST</option>
				<option value="DELETE">DELETE</option>
			</select> <input type="text" id="txt_url"
				value="http://api.localhost/v1" size="80" class="request-form"><br />
			<textarea rows="1" cols="55" readonly="readonly"
				style="border: 0px; font-size: 1.3em;" class="request-form">Request Data</textarea>
			<textarea rows="1" cols="55" readonly="readonly"
				style="border: 0px; font-size: 1.3em;" class="request-form">Response Data</textarea>
			<br />
			<textarea id="postdata" rows="30" cols="68" class="request-form"></textarea>
			<textarea id="responsedata" rows="30" cols="68" class="request-form"></textarea>
			<br />
			<button id="postsubmit">Submit</button>
		</div>
	</center>
</body>
</html>
