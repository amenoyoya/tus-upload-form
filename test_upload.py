import boto3

s3 = boto3.resource('s3') #S3オブジェクトを取得

bucket = s3.Bucket('tus.test')
bucket.upload_file('./README.md', 'test/README.md')
