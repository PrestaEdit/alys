import Foundation
import UIKit
import UniformTypeIdentifiers

final class FileSaverCoordinator: NSObject, UIDocumentPickerDelegate {

    static var active: FileSaverCoordinator?

    private let eventClass: String
    private let tempURL: URL

    init(eventClass: String, tempURL: URL) {
        self.eventClass = eventClass
        self.tempURL = tempURL
    }

    func documentPicker(_ controller: UIDocumentPickerViewController,
                        didPickDocumentsAt urls: [URL]) {
        print("💾 FileSaver: saved '\(tempURL.lastPathComponent)'")
        finish(["success": true])
    }

    func documentPickerWasCancelled(_ controller: UIDocumentPickerViewController) {
        print("💾 FileSaver: cancelled")
        finish(["success": false, "error": "cancelled"])
    }

    private func finish(_ payload: [String: Any]) {
        try? FileManager.default.removeItem(at: tempURL)
        LaravelBridge.shared.send?(eventClass, payload)
        FileSaverCoordinator.active = nil
    }
}

enum FileSaverFunctions {

    class Save: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            guard let filePath = parameters["filePath"] as? String else {
                return ["error": "filePath is required"]
            }
            let source = URL(fileURLWithPath: filePath)
            let filename = parameters["filename"] as? String ?? source.lastPathComponent
            let eventClass = parameters["event"] as? String
                ?? "App\\Events\\Native\\FileSaved"

            guard FileManager.default.fileExists(atPath: filePath) else {
                LaravelBridge.shared.send?(eventClass, ["success": false, "error": "Source file not found"])
                return [:]
            }

            // Copie sous le nom voulu : le sélecteur iOS reprend le nom du fichier exporté.
            // Dans un sous-dossier dédié : la source vit souvent déjà dans tmp/ sous ce même nom,
            // et la supprimer casserait la copie (et un partage ultérieur).
            let exportDir = FileManager.default.temporaryDirectory.appendingPathComponent("FileSaver", isDirectory: true)
            let tempURL = exportDir.appendingPathComponent(filename)
            do {
                try FileManager.default.createDirectory(at: exportDir, withIntermediateDirectories: true)
                try? FileManager.default.removeItem(at: tempURL)
                try FileManager.default.copyItem(at: source, to: tempURL)
            } catch {
                print("💾 FileSaver: error copying file – \(error)")
                LaravelBridge.shared.send?(eventClass, ["success": false, "error": error.localizedDescription])
                return [:]
            }

            DispatchQueue.main.async {
                let coordinator = FileSaverCoordinator(eventClass: eventClass, tempURL: tempURL)
                FileSaverCoordinator.active = coordinator // retain until dismissed

                let picker = UIDocumentPickerViewController(forExporting: [tempURL], asCopy: true)
                picker.delegate = coordinator

                guard
                    let windowScene = UIApplication.shared.connectedScenes
                        .compactMap({ $0 as? UIWindowScene })
                        .first(where: { $0.activationState == .foregroundActive }),
                    let rootVC = windowScene.windows
                        .first(where: { $0.isKeyWindow })?
                        .rootViewController
                else {
                    print("💾 FileSaver: could not find root view controller")
                    FileSaverCoordinator.active = nil
                    try? FileManager.default.removeItem(at: tempURL)
                    return
                }

                rootVC.present(picker, animated: true)
            }

            return [:]
        }
    }
}
