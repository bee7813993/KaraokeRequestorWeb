'AmaRecTVキー入力.vbs
'Copyright (c) 2014 nezuq
'This software is released under the MIT License.
'http://opensource.org/licenses/mit-license.php

Option Explicit
dim arg1,arg2

if WScript.Arguments.Count = 0 then
    WScript.echo("too few arguments.")
    WScript.Quit(-1)
end if
arg1 = WScript.Arguments(0)

'シェルを起動する
Dim wsh
Set wsh = WScript.CreateObject("WScript.Shell")


'AmaRecTVをアクティブにする
Dim wLoc, wSvc, wEnu, wIns
Set wLoc = CreateObject("WbemScripting.SWbemLocator")
Set wSvc = wLoc.ConnectServer
Set wEnu = wSvc.InstancesOf("Win32_Process")
Dim pId
For Each wIns in wEnu
    If Not IsEmpty(wIns.ProcessId) And wIns.Description = arg1 Then
        pId = wIns.ProcessId
    End If
Next
Set wLoc = Nothing
Set wEnu = Nothing
Set wSvc = Nothing
While not wsh.AppActivate(pId) 
    Wscript.Sleep 100 
Wend 


'AmaRecTVでキー入力を再現する
Wscript.Sleep 1000 
wsh.SendKeys "{ESC}",true
Wscript.Sleep 1000 
wsh.SendKeys "%{F4}",true

Set wsh = Nothing