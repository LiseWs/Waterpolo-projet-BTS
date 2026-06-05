' Lance start.bat sans fenêtre console visible
Dim shell
Set shell = CreateObject("WScript.Shell")
shell.Run Chr(34) & WScript.ScriptFullName & Chr(34), 0, False
shell.Run "cmd /c """ & CreateObject("Scripting.FileSystemObject").GetParentFolderName(WScript.ScriptFullName) & "\start.bat""", 0, False
