import Foundation
import UIKit
import UniformTypeIdentifiers

// MARK: - FilePicker Coordinator

/// Manages the UIDocumentPickerViewController lifecycle.
/// Held as a static strong reference so it isn't deallocated before the picker closes.
final class FilePickerCoordinator: NSObject, UIDocumentPickerDelegate {

    static var active: FilePickerCoordinator?

    private let eventClass: String

    init(eventClass: String) {
        self.eventClass = eventClass
    }

    // MARK: UIDocumentPickerDelegate

    func documentPicker(_ controller: UIDocumentPickerViewController,
                        didPickDocumentsAt urls: [URL]) {
        defer { FilePickerCoordinator.active = nil }
        guard let url = urls.first else { return }

        do {
            // asCopy: true means iOS already gave us a local copy — no security scope needed.
            // We still attempt scoped access in case the initialiser flag isn't honoured.
            let secured = url.startAccessingSecurityScopedResource()
            defer { if secured { url.stopAccessingSecurityScopedResource() } }

            let data     = try Data(contentsOf: url)
            let content  = data.base64EncodedString()
            let filename = url.lastPathComponent

            print("📁 FilePicker: picked '\(filename)' (\(data.count) bytes)")

            LaravelBridge.shared.send?(eventClass, ["filename": filename, "content": content])
        } catch {
            print("📁 FilePicker: error reading file – \(error)")
        }
    }

    func documentPickerWasCancelled(_ controller: UIDocumentPickerViewController) {
        FilePickerCoordinator.active = nil
        print("📁 FilePicker: cancelled")
    }
}

// MARK: - Bridge Functions

enum FilePickerFunctions {

    /// Show the native document picker and dispatch the chosen file as a Laravel event.
    /// Parameters:
    ///   - event: (optional) string – fully-qualified Laravel event class name.
    ///            Defaults to "App\\Events\\Native\\FileChosen".
    class Pick: BridgeFunction {
        func execute(parameters: [String: Any]) throws -> [String: Any] {
            let eventClass = parameters["event"] as? String
                ?? "App\\Events\\Native\\FileChosen"

            DispatchQueue.main.async {
                let coordinator = FilePickerCoordinator(eventClass: eventClass)
                FilePickerCoordinator.active = coordinator // retain until dismissed

                let picker = UIDocumentPickerViewController(
                    forOpeningContentTypes: [.data],
                    asCopy: true
                )
                picker.delegate = coordinator
                picker.allowsMultipleSelection = false

                guard
                    let windowScene = UIApplication.shared.connectedScenes
                        .compactMap({ $0 as? UIWindowScene })
                        .first(where: { $0.activationState == .foregroundActive }),
                    let rootVC = windowScene.windows
                        .first(where: { $0.isKeyWindow })?
                        .rootViewController
                else {
                    print("📁 FilePicker: could not find root view controller")
                    FilePickerCoordinator.active = nil
                    return
                }

                rootVC.present(picker, animated: true)
            }

            return [:]
        }
    }
}
