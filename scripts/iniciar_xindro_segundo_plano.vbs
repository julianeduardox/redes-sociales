' ==============================================================================
' XINDRO AI Copilot - Lanzador Silencioso en Segundo Plano para Windows
' ==============================================================================
' Ejecuta el worker de PHP de forma 100% invisible (sin ventana de consola negra).
' Redirige la salida a data/worker_background.log para maxima estabilidad.
' Puedes dejarlo activo y cerrar el navegador web sin interrupciones.

Set WshShell = CreateObject("WScript.Shell")
Set FSO = CreateObject("Scripting.FileSystemObject")

CurrentDir = FSO.GetParentFolderName(WScript.ScriptFullName)
ProjectDir = FSO.GetParentFolderName(CurrentDir)

' Establecer el directorio de trabajo en la raiz del proyecto
WshShell.CurrentDirectory = ProjectDir

PhpExe = "c:\xampp\php\php.exe"
WorkerScript = CurrentDir & "\xindro_worker.php"
DataDir = ProjectDir & "\data"
LogFile = DataDir & "\worker_background.log"

' Verificar o crear directorio data
If Not FSO.FolderExists(DataDir) Then
    FSO.CreateFolder(DataDir)
End If

' Verificar si PHP existe
If Not FSO.FileExists(PhpExe) Then
    MsgBox "No se encontro PHP en: " & PhpExe & vbCrLf & "Verifica la ruta de tu instalacion de XAMPP.", vbCritical, "XINDRO AI Copilot"
    WScript.Quit 1
End If

' Verificar si el script worker existe
If Not FSO.FileExists(WorkerScript) Then
    MsgBox "No se encontro el script del worker en: " & WorkerScript, vbCritical, "XINDRO AI Copilot"
    WScript.Quit 1
End If

' Ejecutar el worker desacoplado con salida protegida hacia worker_background.log
' 0 = Ventana totalmente oculta, False = Asincrono (no bloquea el sistema)
Command = "cmd.exe /c """"" & PhpExe & """ """ & WorkerScript & """ --interval=30 >> """ & LogFile & """ 2>&1"""
WshShell.Run Command, 0, False

WScript.Sleep 1000

' Notificacion sutil de inicio exitoso (se cierra sola en 3 segundos)
WshShell.Popup "XINDRO AI Copilot esta ahora activo en segundo plano." & vbCrLf & _
               "Monitoreando Instagram y Facebook 24/7 sin necesidad de tener el navegador abierto." & vbCrLf & vbCrLf & _
               "Para detenerlo cuando desees, ejecuta 'detener_xindro.bat'.", 3, "XINDRO Copilot Activo", 64
